# Planner Task Assignment Workflow

## 1. Task Assignment Flow

Task Assign
→ Save Assignment
→ Notification Infrastructure Prepared (notification_recipients, notification_deliveries)
→ **NO EMAIL SENT AUTOMATICALLY**
→ User explicitly clicks "Send Mail" in Task Detail Modal
→ Email sent to assigned employees' actual email addresses

## 2. My Tasks

The logged-in user receives only their relevant tasks. Backend filtering ensures only tasks where the authenticated user's employee ID is an assignee (or they are the creator) are returned to My Tasks.

## 3. Task Assign

The assigner can see and manage tasks they have created or are relevant to in the Task Assign view. Assignment creates/updates the task-assignee relationship in `tasks_assignees`.

## 4. Public Tasks

Public tasks (privacy = 'Public') are accessible to all users as defined by existing system logic. Private/user-specific tasks remain private and are only visible to creator/assignees.

## 5. Email Flow (Production)

Employee Assigned
→ notification_recipients.user_id = users.id (where users.EID = employees.id)
→ User clicks "Send Mail"
→ Resolve employee email via users.email
→ Task Assignment Email sent to actual employee email

## 6. Testing (Legacy)

Previously, test validation used only:
- ferojul@artisanbn.com
- firojulislamcse@gmail.com

**These test addresses are NO LONGER USED in production.**

Test mode (MAIL_SENDING_ENABLED=false) prepares notification infrastructure but does not send any email.

## 7. Production

Production recipient resolution:
- notification_recipients.user_id → users.id → users.email
- Manual "Send Mail" action triggers delivery via TasksMailController::sendTaskAssignmentMails()
- TaskAssignNotification::DELIVERY_ENABLED = true
- Sender: noreply@artisanbn.com

Test addresses must not be used as permanent production recipients.
