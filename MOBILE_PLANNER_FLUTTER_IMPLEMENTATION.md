# MOBILE PLANNER — FLUTTER IMPLEMENTATION SPECIFICATION

> **Status:** Authoritative technical spec for implementing the existing Planner module in the Flutter mobile app.
> **Generated from:** Laravel source inspection of `Artisan_HRIS` (no code was modified during analysis).
> **Audience:** Developer / OpenCode agent implementing the Flutter module.
> **Backend source of truth:** `app/Http/Controllers/Mobile/MobileTaskBoardController.php` + `routes/mobile/task_board.php`.

---

## 0. SCOPE & TERMINOLOGY (read first)

This codebase contains **two different things** both nicknamed "task board". Only one of them is the Planner:

| Domain | Tables | API base | Used by Flutter? |
|---|---|---|---|
| **Planner (THIS SPEC)** | `tasks`, `tasks_assignees`, `tasks_checklists`, `tasks_results` | `/api/mobile/task-board/*` | **YES** |
| Workstation Task Board (different feature) | `task_boards`, `task_board_assignees` | `/api/task-board/*` | NO — do not call |
| Web Planner (browser SPA only) | same Planner tables | `/api/planner/tasks/*` | NO — do not call from Flutter |

Despite the URL saying "task-board", the mobile endpoint `/api/mobile/task-board/*` serves the **Planner `tasks` domain**. This naming mismatch is historical; Flutter must use exactly `/api/mobile/task-board/*`.

**Mobile features available:** list, detail, update (field-restricted), checklist toggle/rename, mark-as-read, employee search.
**Mobile features NOT available:** create task, delete task, add/remove assignees, add/delete checklist items. The Flutter UI must not expose these actions (see §7).

---

## 1. ARCHITECTURE

```text
Laravel Planner (source of truth)
  ├── app/Models/Task.php              → enums, relations, date casts
  ├── app/Models/TasksAssignee.php     → assignee rows + read_status
  ├── app/Models/TasksChecklists.php   → checklist rows
  ├── app/Models/TasksResult.php       → status-change history
  ├── database/migrations/*tasks*      → schema
  ├── routes/mobile/task_board.php     → mobile route definitions
  ├── app/Http/Controllers/Mobile/MobileTaskBoardController.php  → MOBILE API (Flutter consumes this)
  ├── app/Http/Controllers/Planner/TasksController.php           → WEB API (browser SPA only, DO NOT use in Flutter)
  └── app/Http/Middleware/MobilePagePermission.php               → page-level permission gate
              ↓  (HTTP JSON + Sanctum Bearer token)
Mobile API  =  /api/mobile/task-board/*   (prefix chain: api → mobile → task-board → auth:sanctum → mobile.permission:task_board)
              ↓
Flutter Planner module
  models → services → repository → state → screens → widgets
```

**Which Laravel component is the source of truth for what:**

| Concern | Source of truth |
|---|---|
| Task field names, enum values | `app/Models/Task.php` constants + `serializeTask()` in `MobileTaskBoardController` |
| Task JSON payload shape | `MobileTaskBoardController::serializeTask()` (L341–409) — **this exact shape** |
| List filtering/search/ordering/limit | `MobileTaskBoardController::index()` (L21–76) |
| Update permission/field filtering | `MobileTaskBoardController::update()` (L110–208) |
| Read/access rule | `MobileTaskBoardController::taskAccessible()` (L298–310) |
| Read-status marking | `MobileTaskBoardController::markRead()` (L312–323) |
| Current employee identity | `Auth::user()->EID` (users.EID → employees.id) — **never a client-supplied id** |
| Feature access (menu visibility) | `user_has_page.Page_Id` must contain page id **73** (`config/mobile.php: 'task_board' => 73`) |
| Auth token | Sanctum personal access token, token name `ArtisanHRIS-Mobile` |

**Flutter golden rule:** send only the Bearer token. Never send `eid`, `user_id`, `employee_id`, or `created_by` in any request body or query — the server derives identity from the token and ignores/rejects client identity.

---

## 2. API ENDPOINTS (mobile Planner)

### 2.0 Common conventions

- **Base URL:** `{BASE_URL}/api` (e.g. `https://host/api`).
- **Auth:** header `Authorization: Bearer {token}` **and** `Accept: application/json` (the `Accept` header is required to receive JSON 401 instead of a redirect — see §11.9).
- **Content type:** `application/json` for all bodies (also for POST/PUT with no body — send `{}`).
- **Success envelope:** always `{"success": true, ...}`; errors `{"success": false, "message": "..."}` (exception: framework 401 is `{"message":"Unauthenticated."}` with no `success` key — §11.9).
- **Middleware stack on every endpoint below:** `api` group (incl. throttle) → `auth:sanctum` → `mobile.permission:task_board`.
- **Rate limit:** `api/mobile/*` = **180 req/min** per user (floor 60, env `MOBILE_API_RATE_LIMIT_PER_MINUTE`). Web `/api/planner/*` = 60/min.
- **Token lifetime:** `config/sanctum.php` `expiration = 360` minutes (6 h absolute), plus rolling idle policy `config/mobile.php` `session_idle_days = 3`. Each new login **revokes** the previous `ArtisanHRIS-Mobile` token → single-device session.
- **Route names:** none exist in this project; address routes by URL only.
- **No pagination metadata anywhere:** no `total`, no `page`, no `next_page`. Only `limit` (§2.1).

---

### 2.1 LIST TASKS

```text
GET /api/mobile/task-board/
```

- **Auth:** required (Sanctum) + page permission 73.
- **Query parameters (all optional):**

| Param | Type | Values | Behavior |
|---|---|---|---|
| `status` | string | any of `Task::STATUSES` or `All` | Ignored when value is exactly `All`. Otherwise `where status = value`. |
| `scope` | string | `assigned` \| `history` | `assigned` → additionally forces `status = 'Not started'`. `history` → additionally forces `status IN ('Not started','In progress')`. See §11.2 — `history` does **not** include `Completed`. |
| `bucket` | string | any `Task::BUCKETS` or `All` | Ignored when `All`. |
| `priority` | string | any `Task::PRIORITIES` or `All` | Ignored when `All`. |
| `privacy` | string | any `Task::PRIVACIES` or `All` | Ignored when `All`. |
| `q` | string | free text | LIKE match on `task_name` OR `notes`; `%`/`_` escaped server-side. |
| `limit` | int | 1–100 | Default **50**; clamped: `min(max(value,1),100)`. |

- **Implicit base scope (always applied):** task is assigned to me **OR** created by me. You can never list tasks unrelated to you, even if `privacy = Public`.
- **Ordering:** `scope=history` → `updated_at DESC, id DESC`; otherwise `created_at DESC, id DESC`.
- **Request body:** none.

**200 success:**

```json
{
  "success": true,
  "data": [ { ...task... }, { ...task... } ]
}
```

Each element is the full task object from §3.

**Errors:**

| Code | Body | Cause |
|---|---|---|
| 401 | `{"message":"Unauthenticated."}` | missing/expired/invalid token (framework shape) |
| 401 | `{"success":false,"message":"Unauthenticated."}` | token ok but `users.EID` is null (middleware) |
| 403 | `{"success":false,"message":"You do not have permission to access this feature."}` | page id 73 missing from `user_has_page` |
| 403 (500) | `{"success":false,"message":"Invalid permission configuration."}` | server config misconfig (HTTP 500) |
| 429 | throttle response | >180 req/min |
| 404/422 | not applicable | this endpoint has no validation |

---

### 2.2 TASK DETAIL

```text
GET /api/mobile/task-board/{id}
```

- **Auth:** required + permission 73. `{id}` numeric (`whereNumber`).
- **Params:** path only.
- **Side effect:** server calls `markRead()` — the caller's `tasks_assignees.read_status` is set to `true` (only if an assignee row exists for the caller; see §11.5).

**200:**

```json
{ "success": true, "data": { ...task from §3... } }
```

**Errors:**

| Code | Body |
|---|---|
| 401 | as §2.1 |
| 403 | `{"success":false,"message":"You do not have access to this task."}` — caller is not assignee, not creator, and `privacy != 'Public'` |
| 404 | `{"success":false,"message":"Task not found."}` (note trailing dot) |

---

### 2.3 UPDATE TASK

```text
PUT   /api/mobile/task-board/{id}
PATCH /api/mobile/task-board/{id}
```

- **Auth:** required + permission 73. Both methods hit the same handler.
- **Body (JSON, any subset — all fields optional):**

| Field | Validation | Editable by |
|---|---|---|
| `task_name` | nullable string max:255 | **creator only** |
| `start_date` | nullable date (`YYYY-MM-DD`) | **creator only** |
| `privacy` | nullable, in `Task::PRIVACIES` | **creator only** |
| `status` | nullable, in `Task::STATUSES` | creator + assignee |
| `bucket` | nullable, in `Task::BUCKETS` | creator + assignee |
| `priority` | nullable, in `Task::PRIORITIES` | creator + assignee |
| `notes` | nullable string | creator + assignee |
| `due_date` | nullable date, `after_or_equal:start_date` | creator + assignee |
| `new_due_date` | nullable date | creator + assignee |
| `repeat` | nullable, in `Task::REPEATS` | creator + assignee |

- `assignees` and `checklists` are **not accepted** by this endpoint (validation would fail them if sent as unknown keys? — Laravel ignores unknown keys, so sending them is a silent no-op; do not send them).
- **Field filtering (runs after validation, silently):** non-creator values of `task_name`, `start_date`, `privacy` are dropped; any other field outside the allowed list is dropped for non-creators; `null` values are dropped for everyone. If nothing survives → **422**.
- **Side effect:** if `status` is present and its value differs from the stored value, a row is inserted into `tasks_results` (`current_status`, `executed_date = today`, `executed_by = caller eid`). This is the status history shown in §3 `results[]`.
- `updated_by` is always set to the caller's eid.

**200 success:**

```json
{
  "success": true,
  "message": "Task updated successfully.",
  "data": { ...full updated task from §3... }
}
```

**Errors:**

| Code | Body | Cause / Flutter handling |
|---|---|---|
| 401 | §2.1 | force logout / refresh token |
| 403 | `{"success":false,"message":"You do not have permission to update this task."}` | not assignee/creator/Public → show "no access" and refresh list |
| 404 | `{"success":false,"message":"Task not found."}` | deleted task → pop detail, refresh list |
| 422 | `{"success":false,"message":"No updatable fields provided."}` | e.g. non-creator sent only `task_name`/`start_date`/`privacy`, or all values null → **UI must pre-disable fields the user cannot edit (§7)** rather than rely on this |
| 422 | `{"success":false,"message":"<validation message>"}` | e.g. `due_date` before `start_date`, bad enum, bad date |
| 422 | `{"success":false,"message":"<exception text>"}` | DB failure (rolled back) |

**Flutter note on `due_date after_or_equal:start_date`:** the rule validates against the `start_date` **present in the same request**. Send only `due_date` (no `start_date` key) when changing a deadline; do not send `start_date: null`.

---

### 2.4 CHECKLIST TOGGLE / RENAME

```text
POST /api/mobile/task-board/{id}/checklists/{checklistId}
```

- **Auth:** required + permission 73. Both ids numeric.
- **Body:**

| Field | Type | Behavior |
|---|---|---|
| `completed_status` | boolean (optional) | `true` → `completed_status=true`, `completed_by = caller eid`. `false` → `completed_status=false`, `completed_by = null`. |
| `checklist_name` | string (optional) | renames the item (anyone with task access). |

- If neither key is present in the payload, nothing changes but `updated_by` is still set and 200 is returned.
- **Access:** requires `taskAccessible` (assignee OR creator OR `privacy == 'Public'`) — checked on the **task**, not the checklist.
- Checklist items are looked up scoped to the task: `tasks_checklists WHERE task_id = {id} AND id = {checklistId}`.

**200:**

```json
{
  "success": true,
  "message": "Checklist updated successfully",
  "data": {
    "id": 11,
    "checklist_name": "Send report",
    "completed_status": true,
    "completed_by": 123,
    "created_by": 45
  }
}
```

(`completed_by` / `created_by` are `employees.id` values, nullable `completed_by`.)

**Errors:** 401 (§2.1) · 403 `{"success":false,"message":"You do not have access to this task."}` · 404 `{"success":false,"message":"Task not found"}` (**no dot**) · 404 `{"success":false,"message":"Checklist not found"}` · 422 validation (`completed_status` not boolean etc., Laravel `{"message","errors"}` shape).

---

### 2.5 MARK AS READ

```text
POST /api/mobile/task-board/{id}/read
```

- **Auth:** required + permission 73. Body: `{}` (no fields read).
- **Behavior:** if a `tasks_assignees` row exists for `(task_id, caller eid)` and `read_status = false`, sets it `true` and `updated_by = caller`. **Silently no-ops** if the caller has no assignee row (e.g. creator who is not an assignee — §11.5). Does **not** check `taskAccessible`.
- **200:** `{"success":true,"message":"Read status updated"}`
- **404:** `{"success":false,"message":"Task not found"}` · 401/403 as §2.1.

---

### 2.6 EMPLOYEE SEARCH (for display / future assignment UI)

```text
GET /api/mobile/task-board/employees
```

- **Auth:** required + permission 73.
- **Query:** `search` (optional) — LIKE on `Full_Name` OR `Employee_Id`.
- **No LIMIT server-side** (payload can be large; §11.7). Debounce input ~300 ms client-side and always send `search`.
- **200:**

```json
{
  "success": true,
  "data": [
    { "id": 123, "Full_Name": "Jane Rahman", "Employee_Id": "EMP-0142" }
  ]
}
```

Ordered by `Full_Name ASC`. `id` is **`employees.id`**.

**Errors:** 401/403 as §2.1.

---

### 2.7 AUTH ENDPOINTS Flutter needs (context)

| Method | URL | Notes |
|---|---|---|
| POST | `/api/mobile/login` | **public** (no token). Body `{"username","password"}` where `username` = `users.name` (login name, NOT email). 200 → see below; 401 `Username or password is incorrect.`; 403 `Your account is inactive.`; 422 validation. |
| GET | `/api/mobile/me` | refresh profile/permissions. |
| GET | `/api/mobile/permissions` | refresh `permissions` + `modules`. |
| POST | `/api/mobile/logout` | revokes current token. |

**Login 200:**

```json
{
  "success": true,
  "message": "Successfully logged in.",
  "data": {
    "token": "1|................................",
    "role_id": 3,
    "permissions": [32, 73, 15, ...],
    "modules": { "task_board": { "id": 73, "name": "Task Board", "icon": "task_alt" }, ... },
    "has_assigned_towns": false,
    "user": {
      "id": 123,
      "Full_Name": "Jane Rahman",
      "Employee_Id": "EMP-0142",
      "img_url": "https://.../x.jpg",
      "department": "Sales",
      "designation": "Executive",
      "supervisor_id": 12
    }
  }
}
```

**Planner gate:** show the Planner/Task Board module only if `data.permissions` contains **`73`** (and/or `data.modules.task_board` exists). Otherwise every planner call returns 403.

---

### 2.8 WEB ENDPOINTS — DO NOT USE FROM FLUTTER

`GET/POST/PATCH/DELETE /api/planner/tasks*`, `/api/planner/tasks/employees`, `/api/planner/tasks/mail*`, `/api/planner/tasks/notifications/*` exist for the browser SPA only. Reasons Flutter must avoid them:

1. Different response shape (`results[]` missing from web serializer; assignee key named `assignee` instead of `employee` in `storeAssignees`).
2. `PATCH /api/planner/tasks/{id}` has **no access control at all** (any authenticated user can edit any task) — a server-side issue documented in §11.8, but it means these endpoints are not the intended mobile contract.
3. `/api/planner/tasks/notifications/*` and `/api/planner/tasks/mail*` **send e-mail** — never called from the app.
4. Stricter rate limit (60/min).

---

## 3. TASK MODEL (exact API payload)

Single canonical object returned in `data` (detail) or `data[]` (list). Serialized by `MobileTaskBoardController::serializeTask()`.

| Field | Type | Nullable | Meaning | Example |
|---|---|---|---|---|
| `id` | int | no | `tasks.id` | `451` |
| `task_name` | string | no | Title | `"Prepare Q4 sales deck"` |
| `start_date` | string `YYYY-MM-DD` | yes | Start date (cast `date:Y-m-d`) | `"2026-10-01"` |
| `due_date` | string `YYYY-MM-DD` | yes | Due date | `"2026-10-07"` |
| `new_due_date` | string `YYYY-MM-DD` | yes | Extension / revised due date | `"2026-10-10"` or `null` |
| `status` | enum string | no | see §4 | `"In progress"` |
| `repeat` | enum string | no | see §4 | `"Do not repeat"` |
| `bucket` | enum string | no | see §4 | `"Up next"` |
| `priority` | enum string | no | see §4 | `"Important"` |
| `privacy` | enum string | no | see §4 | `"Private"` |
| `notes` | string | yes | Description/body | `"Attach forecast sheet"` |
| `created_by` | int | no | **`employees.id`** of creator | `123` |
| `updated_by` | int | yes | **`employees.id`** of last updater | `123` or `null` |
| `created_by_info` | object\|null | yes | Creator snapshot | see below |
| `assignees` | array | no (may be empty) | assignee rows | see below |
| `checklists` | array | no (may be empty) | checklist items | see below |
| `results` | array | no (may be empty) | status-change history | see below |
| `checklist_progress` | object | no | counts | `{"completed": 2, "total": 5}` |
| `my_read_status` | bool | no | whether **caller's** assignee row is read | `false` |

### `created_by_info` (employee snapshot)

```json
{ "id": 123, "Full_Name": "Jane Rahman", "Employee_Id": "EMP-0142" }
```
or `null` if the employee row is missing/deleted.

### `assignees[]`

| Field | Type | Nullable | Meaning |
|---|---|---|---|
| `id` | int | no | `tasks_assignees.id` (row id, **not** employee id) |
| `assigned_to` | int | yes (col nullable) | **`employees.id`** of assignee |
| `assigned_by` | int | no | **`employees.id`** of assigner |
| `employee` | object\|null | yes | `{id, Full_Name, Employee_Id}` snapshot of `assigned_to` |
| `read_status` | bool | no | per-assignee read flag |
| `is_current_user` | bool | no | `assigned_to == caller eid` — use this to highlight "me" |

> Note: the **web** `storeAssignees` response uses the key `assignee` instead of `employee`. The mobile serializer always uses `employee`. Flutter must parse `employee`.

### `checklists[]`

| Field | Type | Nullable | Meaning |
|---|---|---|---|
| `id` | int | no | `tasks_checklists.id` |
| `checklist_name` | string | yes (col nullable) | item text |
| `completed_status` | bool | no | done flag |
| `completed_by` | int\|null | yes | **`employees.id`** who ticked it; `null` when unticked |
| `created_by` | int | no | **`employees.id`** |

### `results[]` (status history)

| Field | Type | Nullable | Meaning |
|---|---|---|---|
| `id` | int | no | `tasks_results.id` |
| `current_status` | string | no | status value at that change (plain string column, not a DB enum) |
| `executed_date` | string `YYYY-MM-DD`\|null | yes | date of change |
| `executed_by` | int\|null | yes | **`employees.id`** who changed status |
| `updated_by` | int\|null | yes | **`employees.id`** |

Rows are appended whenever `status` changes via **either** mobile or web update. **Ordering is not specified** by the server (no `orderBy`); Flutter should sort client-side by `executed_date`/`id` ascending for a timeline.

### Minimal parse example (Dart conceptual)

```dart
class Task {
  final int id;
  final String taskName;
  final DateTime? startDate, dueDate, newDueDate;
  final String status, repeat, bucket, priority, privacy;
  final String? notes;
  final int createdBy; final int? updatedBy;
  final EmployeeRef? createdByInfo;
  final List<TaskAssignee> assignees;
  final List<ChecklistItem> checklists;
  final List<TaskResult> results;
  final int checklistCompleted, checklistTotal;
  final bool myReadStatus;
}
```
All keys are **lowercase snake_case** exactly as listed; `Full_Name` / `Employee_Id` inside employee objects are **PascalCase with underscore** — parse them as-is.

---

## 4. ENUMS (exact API values — do not invent, do not case-change)

From `app/Models/Task.php` (also mirrored in DB `enum` columns on `tasks`):

**`status`** (`Task::STATUSES`)
```text
"Not started"
"In progress"
"Completed"
```

**`bucket`** (`Task::BUCKETS`)
```text
"No bucket"
"Backlog"
"Up next"
"In progress"
"Blocked"
"Completed"
```
> Note `"In progress"` and `"Completed"` appear in **both** `status` and `bucket` — they are independent fields.

**`priority`** (`Task::PRIORITIES`)
```text
"Urgent"
"Important"
"Medium"
"Low"
```

**`repeat`** (`Task::REPEATS`)
```text
"Do not repeat"
"Daily"
"Custom"
```

**`privacy`** (`Task::PRIVACIES`)
```text
"Public"
"Private"
"Shared"
"Others"
```

**Special non-enum query values** (list endpoint only): `status=All`, `bucket=All`, `priority=All`, `privacy=All`, `scope=assigned`, `scope=history`. `All` must be sent with exact capital **A** (server compares `!== 'All'`).

Recommended: define `abstract final class TaskEnums { static const statuses = [...]; ... }` and validate/serialize through it everywhere.

---

## 5. EMPLOYEE IDENTITY (critical)

```text
users.id        ← auth subject (Sanctum token belongs to this)
users.EID       →  employees.id   (FK, nullable, unique)   ← EVERYTHING in Planner uses this
employees.id    ← the id used by the entire Planner domain
```

**Planner uses `employees.id` — never `users.id`.**

Evidence in the API:
- `tasks.created_by`, `tasks.updated_by`
- `tasks_assignees.assigned_to`, `assigned_by`, `updated_by`
- `tasks_checklists.completed_by`, `created_by`, `updated_by`
- `tasks_results.executed_by`, `updated_by`
- `employeePayload()` / `employees` endpoint `id` → `employees.id`
- `is_current_user` comparison → `assigned_to == Auth::user()->EID`

**How Flutter identifies the current employee:**

1. At login, `data.user.id` is **`employees.id`** (the login query is built with `->where('employees.id', $user->EID)`). Store it as `currentEmployeeId`.
2. `GET /api/mobile/me` → `data.user.id` returns the same value — use it to refresh/verify.
3. **Do not** store or send `users.id` for planner purposes. If your auth layer also keeps `users.id`, keep them in clearly separate fields.
4. **Never** send identity to the planner API. The server does `$currentEid = (int) Auth::user()->EID;` on every call (explicit code comment: *"Identity always comes from Auth::user()->EID — never from the client."*).
5. Client-side "is this my task" checks:
   - `task.created_by == currentEmployeeId` → I am creator
   - `task.assignees.any((a) => a.is_current_user)` or `a.assigned_to == currentEmployeeId` → I am assignee
   - prefer the server-provided `is_current_user` and `created_by_info` when present.
6. **Edge case:** a `users` row with `EID = null` cannot use Planner at all — `MobilePagePermission` returns 401 `{"success":false,"message":"Unauthenticated."}` even with a valid token. Handle by treating it as "profile incomplete", force re-login/contact admin, not as a generic network error.
7. Employee snapshots (`Full_Name`, `Employee_Id`) can go stale; re-fetch via `/task-board/employees` when needed. `Employee_Id` is the human code (e.g. `EMP-0142`), `Full_Name` is display name.

---

## 6. TASK ACTIONS — HOW FLUTTER SHOULD IMPLEMENT EACH

### 6.1 Task list
- Call `GET /api/mobile/task-board/?limit=50` (+ filters) on screen enter and on pull-to-refresh.
- Default tab (no `scope`) = everything assigned to me or created by me, newest first.
- Recommended tabs: **Assigned** (`scope=assigned`), **History** (`scope=history`), **All** (no scope). See §11.2 for the `history` caveat before naming the tab "Completed".
- `limit` max 100 and there is **no offset** → beyond 100 items the list is truncated server-side. Show a footer hint if `data.length == limit` ("Showing first N tasks — refine filters"). Do not attempt offset paging; it does not exist.
- Optimistic UI allowed for checklist toggle / status change (§6.3) with rollback on error.

### 6.2 Task detail
- `GET /api/mobile/task-board/{id}`.
- Opening detail **auto-marks read** server-side → after a successful 200, set local `myReadStatus = true` and update the list item (do not call `/read` again unless you need to).
- On 403 → show "No access" state and pop; on 404 → show "Task deleted" and pop + refresh list.

### 6.3 Task update
- `PUT /api/mobile/task-board/{id}` with **only changed, permitted fields** (never send the whole object — sending an unchanged enum is fine but sending `task_name` as a non-creator triggers 422 `No updatable fields provided.` if it's the only field).
- Status change: send `{"status": "Completed"}` → server appends a `tasks_results` row → refetch detail so `results[]` shows the new timeline entry.
- On 200, replace local task with response `data` (it is the authoritative post-update object).
- **UI must pre-apply the field matrix (§7)** to disable/hide fields the current user cannot edit, because the server drops them *silently* (only returning 422 if nothing at all survives).

### 6.4 Checklist toggle
- `POST /api/mobile/task-board/{taskId}/checklists/{checklistId}` body `{"completed_status": true|false}`.
- Optimistic tick + animate progress bar using `checklist_progress`; recompute locally as `completed ±1 / total`.
- On error, revert. On 404, refetch detail (item deleted elsewhere).
- There is **no** mobile endpoint to add/delete checklist items — render existing items only.

### 6.5 Mark as read
- Primary path: opening detail (server-side side effect).
- For list-level "mark read" button or unread badge clearing: `POST /api/mobile/task-board/{id}/read` with `{}`.
- Unread indicator = `!my_read_status`. Remember creators without an assignee row always see `false` (§11.5) — do not build a "read count" metric on it.

### 6.6 Employee search
- `GET /api/mobile/task-board/employees?search=<query>`, debounce 300 ms, case-insensitive server LIKE.
- Result `id` is `employees.id`.
- Use it for display/mention-style UI only — **there is no assign API on mobile**, so do not build an assignee picker that saves.

### 6.7 Filters
- Send only non-`All` values: `status`, `bucket`, `priority`, `privacy`, `scope`, `q`, `limit`.
- Keep filters in state (e.g. a `PlannerFilter` value class), serialize to query params, show active-filter chips with clear-all.
- Filters are **AND**-combined server-side. `scope` adds an additional status constraint on top of `status` → avoid offering `status` and `scope` simultaneously (they intersect, e.g. `scope=assigned&status=Completed` = always empty; §11.2).

### 6.8 Search
- `q` hits `task_name` OR `notes`. Debounce 300–400 ms; clear `q` when the user clears the box (omitting the param, not sending empty string — server uses `filled()` so `q=` is ignored anyway).
- Search is **server-side**; do not also filter the returned array client-side.

### 6.9 Refresh
- Pull-to-refresh → re-run current list query.
- After any successful mutation → update item in place from response `data` (list) or refetch detail.
- Global: on 401 → attempt token/refresh strategy or route to login; on 403 permission message → check page id 73 (§2.7).

---

## 7. PERMISSION RULES (from the existing Laravel implementation only)

### 7.1 Feature-level gate (before any endpoint)
- `mobile.permission:task_board` middleware requires `user_has_page.Page_Id` JSON arrays (for `EID`) to contain **73**. Otherwise **403** on *every* planner endpoint.
- `users.EID` must be non-null, else **401**.

### 7.2 Task access (`taskAccessible`) — applies to detail, update, checklist toggle

```text
accessible  ⟺  caller is an assignee   OR   caller is the creator   OR   task.privacy == 'Public'
```

- There is **no admin bypass** on mobile (unlike the web controller, where `users.role_id ∈ {1,2}` bypasses). Even an admin sees 403 on a `Private` task they neither created nor were assigned.
- `Shared` and `Others` privacy values behave exactly like `Private` for access purposes (only literal `'Public'` opens access).

### 7.3 Update rights (field-level, mobile `update()`)

| Role | Identified by | Can edit |
|---|---|---|
| **Creator** | `task.created_by == eid` | `task_name`, `start_date`, `privacy`, `status`, `bucket`, `priority`, `notes`, `due_date`, `new_due_date`, `repeat` (i.e. all validated fields) |
| **Assignee (not creator)** | row in `assignees` with `assigned_to == eid` | `status`, `bucket`, `priority`, `notes`, `due_date`, `new_due_date`, `repeat` |
| **Public-task viewer (neither)** | `privacy == 'Public'` | same as assignee **for updating via API** (access passes), but UI should present read-only unless they are assignee — server does not distinguish assignee vs public viewer once `taskAccessible` passes; only the creator-only filter applies |
| **Everyone else** | — | nothing (403) |
| **Nobody on mobile** | — | create task, delete task, add/remove assignees, add/delete checklist items — **no endpoint exists** |

Additional notes:
- `checklist_name` rename inside `updateChecklist` requires only `taskAccessible` (no creator check).
- `markReadStatus` requires only a valid task id (no access check).
- Read-status is per-assignee row; only the caller's own row can be flipped.

### 7.4 What other users experience
- Assignees see tasks assigned to them regardless of `privacy`.
- Non-assignees see a task in their list **only if they created it** (list base scope), or can open it directly if `privacy == 'Public'` (detail accessible, though it may not appear in their list).
- Nothing on mobile deletes data; destructive admin actions stay on the web SPA.

---

## 8. FLUTTER RECOMMENDED STRUCTURE

```text
lib/
├── core/
│   ├── network/api_client.dart          # Dio instance: baseUrl, AuthInterceptor (Bearer token,
│   │                                     #   Accept: application/json), 401 → re-login flow,
│   │                                     #   429 backoff, timeout, error envelope parsing
│   ├── error/app_exception.dart         # ApiException(code, message, success=false) mapping
│   └── config/planner_config.dart       # base path '/api/mobile/task-board', default limit=50
├── models/
│   ├── task.dart                        # Task + fromJson/toJson (§3), checklist_progress parsing
│   ├── task_assignee.dart               # assignees[] incl. employee ref + is_current_user
│   ├── checklist_item.dart
│   ├── task_result.dart                 # results[] history entry
│   ├── employee_ref.dart                # {id, Full_Name, Employee_Id} (PascalCase keys!)
│   ├── planner_enums.dart               # §4 exact strings + isIn lists
│   └── planner_filter.dart              # status/scope/bucket/priority/privacy/q/limit → queryParams
├── services/
│   ├── auth_service.dart                # login/me/logout, stores token + currentEmployeeId
│   └── planner_api_service.dart         # thin typed methods per endpoint (§2), no business logic
├── repository/
│   └── planner_repository.dart          # composes service calls, maps DTOs → entities,
│                                       #   error normalization, read-through caching of list/detail
├── state/                               # (Riverpod/Bloc/Provider — pick one, keep consistent)
│   ├── planner_list_controller.dart     # list + filters + pagination-limit + pull-to-refresh + tabs(scope)
│   ├── planner_detail_controller.dart   # detail load, auto-read bookkeeping, mutations
│   ├── checklist_controller.dart        # optimistic toggle + progress recompute + rollback
│   └── planner_filter_notifier.dart     # active filter chips
├── screens/
│   ├── planner/planner_list_screen.dart     # tabs + search + filter bar + list
│   ├── planner/task_detail_screen.dart      # sections: header, meta, checklist, assignees, history
│   └── planner/widgets/...                  # (or widgets/ below)
├── widgets/
│   ├── task_card.dart                   # list row
│   ├── status_chip.dart / priority_badge.dart / bucket_chip.dart / privacy_badge.dart
│   ├── checklist_tile.dart              # checkbox + progress
│   ├── assignee_avatar_stack.dart
│   ├── task_history_timeline.dart       # results[]
│   ├── filter_sheet.dart / filter_chip_row.dart
│   ├── planner_empty_state.dart / planner_error_state.dart / shimmer_list.dart
│   └── due_date_label.dart              # overdue/today/upcoming coloring
```

**Responsibility split:**
- `models/` — pure data, JSON in/out, no Flutter widgets, no HTTP. Enum validation helpers live here.
- `services/` — HTTP only: build request, return parsed JSON / throw `ApiException`. Knows URLs and status codes, nothing about UI state.
- `repository/` — use-case-facing API: returns typed entities, decides caching, combines endpoints (e.g. detail + employees), normalizes errors (`403-no-access`, `404-deleted`, `422-validation`).
- `state/` — loading/error/data state machines, optimistic updates, filter state, refresh triggers. One controller per screen concern.
- `screens/` — composition, navigation, loading/empty/error branches, gestures.
- `widgets/` — reusable presentational components with no data fetching.

---

## 9. UI REQUIREMENTS (modern enterprise: Planner / Linear / Jira inspired)

- **Task Board list screen**
  - Top app bar: title "Planner", search icon (expands to inline search field), filter icon with active-count badge.
  - Segmented tabs: **Assigned | History | All** (maps to `scope`). Scrollable chips row below for quick status/priority filters.
  - List of task cards, newest first; pull-to-refresh; shimmer skeleton on first load.
  - Sticky "Showing N tasks" hint when `data.length == limit`.
- **Task card**
  - Row: status dot/chip (left), task name (max 2 lines, strikethrough or muted when `Completed`), priority badge (color: Urgent=red, Important=amber, Medium=blue, Low=gray).
  - Second line: due date (red if overdue, amber if due today), bucket chip if not `No bucket`, checklist progress `2/5` with mini progress bar, unread dot when `!my_read_status`.
  - Trailing: assignee avatar stack (initials circles) + `is_current_user` ring highlight.
- **Detail screen**
  - Header: editable title (creator only), status dropdown (segmented or dropdown), priority selector, bucket selector.
  - Meta grid: start date, due date, new due date (show "extended" tag when `new_due_date != null`), repeat, privacy, creator (`created_by_info.Full_Name` + `Employee_Id`), last updated.
  - Notes section (editable per permission matrix §7.3).
  - **Checklist** section: checkbox tiles with progress header; only existing items (no add button); toggle calls §6.4.
  - **Assignees** section: read-only list of `employee.Full_Name` / `Employee_Id` with per-person read indicator (`read_status`); highlight "You".
  - **History** timeline from `results[]`: "Completed — 2026-10-05 — by Jane (EMP-0142)".
  - FAB/save button visible only when at least one editable field changed.
- **Status/Priority/Badge**: exact enum strings from §4 as labels; consistent color map defined once in `planner_enums.dart`/theme. Never display raw API casing differences (server values are already human-readable, e.g. `Not started`).
- **Filters**: bottom sheet with sections Status / Bucket / Priority / Privacy (+ "All" reset), apply/clear; active filters shown as removable chips under the tabs.
- **Search**: debounced server-side `q`; clear button; empty-result message distinct from error.
- **Loading**: skeleton cards for list; circular progress in detail; button-level spinner during mutations (disable double-submit).
- **Empty state**: illustration + "No tasks yet" + "Pull to refresh"; different copy per tab (e.g. "No completed history").
- **Error state**: inline retry card with the server `message`; special branches: 403 → "You don't have access / feature not enabled", 404 → "Task no longer exists", 401 → session expired → login, 422 → snackbar with message, 429 → "Too many requests, slow down" + auto-retry with backoff.
- **Forbidden UI (no backend support):** New Task button, Delete task, Assign/remove people, Add/delete checklist items. Hide them entirely rather than show disabled.

---

## 10. COMPLETE FLUTTER FLOW

```text
Login
 POST /api/mobile/login  {username, password}
   → 200 { data.token, data.permissions[], data.modules, data.user{id: employees.id, ...} }
   → store token (secure storage) + currentEmployeeId = data.user.id
   → gate Planner on permissions contains 73
        ↓
Token attached to every request
 Authorization: Bearer <token>   Accept: application/json
        ↓
Planner API
 GET /api/mobile/task-board/?limit=50&scope=assigned
   → 200 { success:true, data:[Task...] }   (401 → login, 403 → permission screen)
        ↓
Task List  (state: loading → data | empty | error)
   user taps filter/search/tab → new query params → refetch (pull-to-refresh same)
        ↓
Task Detail
 GET /api/mobile/task-board/{id}
   → 200 { success:true, data:Task }   (server marks my_read_status=true side effect)
   → 403 → no-access state ; 404 → deleted → pop + refresh list
        ↓
Task Actions
   toggle checklist → POST .../checklists/{cid} {completed_status}
        → 200 { data:{...} }  (optimistic update, rollback on error)
   change field(s)  → PUT .../{id} {status:"Completed", notes:"..."}
        → 200 { message, data:Task }  → replace local task (results[] gains history row)
        → 403/404/422 → per §2.3 handling
   mark read (list) → POST .../{id}/read {}
        → 200 { message }  → clear local unread dot
        ↓
API responses always re-derive list badges from returned data;
mutations never trust client-only success — only server 200 with success:true.
```

**Request/response invariants for the client:**
1. Every request: Bearer token + `Accept: application/json` + JSON body `{}` when empty.
2. Every response: branch on HTTP code first, then `success` boolean, then `message`/`data`.
3. Never send identity fields; derive "me" from `currentEmployeeId` + `is_current_user`/`created_by`.
4. Treat `data` returned by any mutation as authoritative and replace local state with it.
5. Enum values are strings from §4 — round-trip them unchanged.

---

## 11. KNOWN ISSUES / WARNINGS (report only — DO NOT FIX in this task)

### 11.1 Uncommitted rewrite of the mobile controller
- **Problem:** `app/Http/Controllers/Mobile/MobileTaskBoardController.php` is modified in the working tree and **not committed** (`git status` shows ` M ...`). The committed HEAD version targets `TaskBoard`/`task_boards`, filters on a non-existent `eid` column, and lacks `employees`, `updateChecklist`, `markReadStatus` — i.e. three routed methods don't exist at HEAD.
- **Root cause:** in-progress refactor from the Workstation `task_boards` domain to the Planner `tasks` domain, committed routes (`4c2844a changes and make for flutter apps api`) ahead of the controller rewrite.
- **Flutter handling:** implement against the **current working-tree behavior documented in §2–§7** (that is what runs on this machine). Before releasing, confirm with the backend owner that this file is committed; if the deployed server still runs HEAD, three endpoints will 500/404 and `index` will behave differently.

### 11.2 `scope=history` excludes `Completed` (and scope/status intersect)
- **Problem:** `scope=history` filters `status IN ('Not started','In progress')` — completed tasks never appear; a "History" tab will look broken. Also `scope` is applied **in addition to** `status`, so `scope=assigned&status=Completed` yields an empty list (AND of contradictory conditions).
- **Root cause:** `MobileTaskBoardController::index()` L36–41 adds `whereIn` on top of the earlier `where status = ...`.
- **Flutter handling:** do not label the tab "Completed/History" expecting finished work — or omit `scope=history` and use `status=Completed` for a completed view. Never send `scope` and `status` together; enforce one source of truth for status filtering in the UI.

### 11.3 No create / delete / assignee / checklist-add endpoints on mobile
- **Problem:** mobile surface supports read + partial update only; the web SPA has full CRUD (`/api/planner/tasks*`).
- **Root cause:** `routes/mobile/task_board.php` registers only 7 routes by design.
- **Flutter handling:** omit those actions in UI entirely. Do not "fall back" to `/api/planner/tasks*` (different shape, weaker auth, mail side effects).

### 11.4 No pagination beyond `limit ≤ 100`
- **Problem:** no `page`/`offset`/`total`; tasks beyond the first 100 (by the defined order) are unreachable via API.
- **Root cause:** `$query->limit($limit)->get()` only.
- **Flutter handling:** default `limit=50`, raise to 100 when needed, rely on filters/search to narrow; show a truncation hint when `data.length == limit`.

### 11.5 `my_read_status` is always `false` for creators who are not assignees
- **Problem:** `markRead()` only updates a `tasks_assignees` row; none exists for a pure creator, so detail views / `/read` calls silently no-op and the unread dot never clears.
- **Root cause:** read state is modeled per-assignee row only (`tasks_assignees.read_status`), no read model for creators.
- **Flutter handling:** for creator-only tasks, drive the read/unread UI from local storage (e.g. last-opened timestamp per task id) or suppress the unread dot when the user is not an assignee; do not build analytics on this field.

### 11.6 Silent field drops + misleading 422
- **Problem:** non-creator sends `task_name` → field silently ignored; if it was the only field, response is **422 `No updatable fields provided.`** rather than 403. Users perceive "save broken".
- **Root cause:** `update()` filters `$creatorOnlyFields` / `$allowedFields` after validation and returns 422 when the filtered set is empty.
- **Flutter handling:** apply the §7.3 matrix in the UI — read-only title/dates/privacy for non-creators — and map 422 `No updatable fields provided.` to "You can't edit this field" instead of a generic validation error.

### 11.7 `GET .../employees` has no server-side limit
- **Problem:** `?search=` omitted (or very short) returns the **entire** employee table as JSON.
- **Root cause:** the `->limit(200)` is commented out in the web controller and absent in the mobile one.
- **Flutter handling:** always send a non-empty `search` of ≥2–3 chars, debounce, min-char gate; never prefetch the full list.

### 11.8 Web Planner endpoints have inconsistent/missing authorization (do not expose)
- **Problem:** `Planner\TasksController@update`, `storeAssignees`, `destroyAssignee`, and checklist endpoints perform **no `taskAccessible` check** — any authenticated user can mutate any task id via the web API. Web `index` also reads an `eid` query param and ignores it. Web serializer omits `results[]`; `storeAssignees` returns key `assignee` instead of `employee`.
- **Root cause:** access checks were added to `show`/`destroy` only; mobile rewrite added checks but web was not aligned.
- **Flutter handling:** never call `/api/planner/tasks*` from Flutter (§2.8). Report to backend owner separately; out of scope for this documentation task.

### 11.9 401 has two different shapes + redirect risk
- **Problem:** framework `auth:sanctum` failure returns `{"message":"Unauthenticated."}` (no `success` key), while `MobilePagePermission` returns `{"success":false,"message":"Unauthenticated."}`. Also `Authenticate::redirectTo()` returns `'/'` for **non-JSON** requests → an client omitting `Accept: application/json` can receive a **302 redirect** instead of 401.
- **Root cause:** Laravel default error envelope + custom middleware envelope; `app/Http/Middleware/Authenticate.php` L16.
- **Flutter handling:** always send `Accept: application/json`; treat any 401/302 response as session-expired regardless of body shape; parse `message ?? 'Session expired'`.

### 11.10 Inconsistent message punctuation
- **Problem:** trailing-dot inconsistency across endpoints: `"Task not found."` (detail/update) vs `"Task not found"` (checklist/read) vs `"Checklist not found"`. Same for success messages (`"Task updated successfully."` vs `"Checklist updated successfully"`).
- **Root cause:** hand-written literals per method.
- **Flutter handling:** never string-match messages; branch on HTTP status + `success` + endpoint context. Use messages only for display.

### 11.11 Two features share the `/task-board` URL family
- **Problem:** `/api/task-board/*` (Workstation `task_boards`, statuses `Assigned/Complete/Incomplete`) vs `/api/mobile/task-board/*` (Planner `tasks`, statuses `Not started/In progress/Completed`). Confusing for developers and for log analysis.
- **Root cause:** naming reuse during the mobile rewrite.
- **Flutter handling:** hard-code only the `mobile` path in `planner_config.dart`; add a comment in code so nobody "unifies" them.

### 11.12 `users.role_id` / `users.active` have no migration
- **Problem:** login checks `$user->active != 1` and admin logic uses `role_id ∈ {1,2}` but neither column exists in the repo's migrations (live-DB/legacy columns). On a fresh DB, `active` checks are skipped (`isset` guard) and role checks silently evaluate false.
- **Root cause:** columns exist only in the production database, not in version-controlled schema.
- **Flutter handling:** ignore `role_id` for planner logic entirely — mobile planner has **no admin bypass** anyway (§7.2). Gate purely on `permissions` (page id 73).

### 11.13 `results[]` ordering undefined
- **Problem:** history rows come back in unspecified order (no `orderBy` in `serializeTask`/eager load).
- **Root cause:** `Task::results()` plain `hasMany`.
- **Flutter handling:** sort client-side (`executed_date` then `id`, ascending) before rendering the timeline.

### 11.14 Checklist items cannot be added or deleted on mobile
- **Problem:** UI that implies "+ Add checklist" will have no endpoint; a 404 on toggle usually means an admin deleted the item on web.
- **Root cause:** by design (§11.3).
- **Flutter handling:** render checklist read-only-plus-toggle; on 404 refetch detail and show "checklist item was removed".

### 11.15 No API documentation or tests exist in the repo
- **Problem:** no OpenAPI/Swagger/Postman/apidoc; `tests/` contain only examples; route files carry no route names. This document is therefore the first authoritative spec.
- **Root cause:** never generated.
- **Flutter handling:** treat this file as the contract; if backend behavior disagrees, re-inspect `MobileTaskBoardController` and update this spec rather than guessing.

### 11.16 Notification/mail endpoints exist under `/api/planner/tasks/*`
- **Problem:** five `/notifications/*` endpoints plus `/mail/{id}/assignees` **send real e-mail** and return HTTP 200 even when skipped; they are not called by the web UI.
- **Root cause:** server-side notification pipeline (`TasksMailController`), dormant triggers.
- **Flutter handling:** **never call them** from the app (also satisfies the no-mail constraint of this task).

---

## 12. QUICK REFERENCE CARD

```text
BASE            {BASE_URL}/api
LIST            GET    /mobile/task-board/?status&scope&bucket&priority&privacy&q&limit
DETAIL          GET    /mobile/task-board/{id}
UPDATE          PUT    /mobile/task-board/{id}         (subset body, field-filtered server-side)
CHECKLIST       POST   /mobile/task-board/{id}/checklists/{checklistId}  {completed_status}
READ            POST   /mobile/task-board/{id}/read    {}
EMPLOYEES       GET    /mobile/task-board/employees?search=
LOGIN           POST   /mobile/login                   {username, password}  (public)

AUTH            Authorization: Bearer <token>   +   Accept: application/json
IDENTITY        users.EID -> employees.id   (server-side only; never send it)
PERMISSION      page id 73 in permissions[]; feature gate for every call
ENUMS           status : Not started | In progress | Completed
                bucket : No bucket | Backlog | Up next | In progress | Blocked | Completed
                priority: Urgent | Important | Medium | Low
                repeat : Do not repeat | Daily | Custom
                privacy: Public | Private | Shared | Others
ACCESS          assignee OR creator OR privacy == 'Public'   (no admin bypass on mobile)
EDIT (creator)  task_name, start_date, privacy, status, bucket, priority, notes, due_date, new_due_date, repeat
EDIT (others)   status, bucket, priority, notes, due_date, new_due_date, repeat
NOT AVAILABLE   create, delete, assignees add/remove, checklist add/delete, mail/notifications
```

---

*End of specification. Code changes made during generation: NONE. Mail/notifications sent: NONE.*
