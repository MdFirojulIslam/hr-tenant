<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> Task Notification</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    body {
      background-color: #f4f5f7;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }

    .email-card {
      background-color: #ffffff;
      width: 100%;
      max-width: 600px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      border: 1px solid #e5e7eb;
    }

    .card-header {
      background-color: #0d1322;
      color: #ffffff;
      padding: 18px 28px;
      font-size: 16px;
      font-weight: 700;
      letter-spacing: -0.2px;
    }

    .card-body {
      padding: 32px 28px;
    }

    .category-tag {
      text-transform: uppercase;
      color: #6b7280;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    .main-title {
      color: #111827;
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 20px;
      line-height: 1.3;
    }

    .greeting {
      color: #374151;
      font-size: 14px;
      margin-bottom: 12px;
    }

    .message {
      color: #374151;
      font-size: 14px;
      margin-bottom: 28px;
    }

    /* Details Table Styling */
    .details-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 28px;
    }

    .details-table tr:not(:last-child) td {
      border-bottom: 1px solid #f3f4f6;
    }

    .details-table td {
      padding: 12px 16px;
      font-size: 13px;
      vertical-align: middle;
    }

    .label-col {
      color: #6b7280;
      text-transform: uppercase;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.5px;
      width: 40%;
    }

    .value-col {
      color: #111827;
      font-weight: 700;
      width: 60%;
    }

    /* Action Button */
    .btn-action {
      display: inline-block;
      background-color: #2563eb;
      color: #ffffff;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      padding: 10px 20px;
      border-radius: 6px;
      margin-bottom: 32px;
      transition: background-color 0.2s ease;
    }

    .btn-action:hover {
      background-color: #1d4ed8;
    }

    /* Footer Text */
    .footer-text {
      color: #9ca3af;
      font-size: 12px;
      line-height: 1.5;
    }
  </style>
</head>

<body>

  <div class="email-card">
    <!-- Header -->
    <div class="card-header">
      Task Notification
    </div>

    <!-- Body Content -->
    <div class="card-body">
      <div class="category-tag">Task Update</div>
      <h1 class="main-title">You have been assigned to task: {{ $task_name }}</h1>

      <p class="greeting">Hello {{ $assignee_name }},</p>
      <p class="message">{{ $assigned_by_name }} assigned you to the task "{{ $task_name }}".</p>

      <!-- Metadata Table -->
      <table class="details-table">
        <tr>
          <td class="label-col">Task</td>
          <td class="value-col">{{ $task_name }}</td>
        </tr>
        <tr>
          <td class="label-col">Task ID</td>
          <td class="value-col">#{{ $task_id }}</td>
        </tr>
        <tr>
          <td class="label-col">New Assignee(s)</td>
          <td class="value-col">{{ $assignee_name }}</td>
        </tr>
        <tr>
          <td class="label-col">Assigned By</td>
          <td class="value-col">{{ $assigned_by_name }}</td>
        </tr>
        <tr>
          <td class="label-col">Status</td>
          <td class="value-col">{{ $status ?? '—' }}</td>
        </tr>
        <tr>
          <td class="label-col">Priority</td>
          <td class="value-col">{{ $priority ?? '—' }}</td>
        </tr>
        <tr>
          <td class="label-col">Start Date</td>
          <td class="value-col">{{ $start_date ?? '—' }}</td>
        </tr>
        <tr>
          <td class="label-col">Due Date</td>
          <td class="value-col">{{ $due_date ?? '—' }}</td>
        </tr>
        <tr>
          <td class="label-col">Assigned At</td>
          <td class="value-col">{{ $assigned_at }}</td>
        </tr>
      </table>

      <!-- CTA Button -->
      <a href="{{ $action_url }}" class="btn-action">Open task #{{ $task_id }}</a>

      <!-- Footer -->
      <p class="footer-text">
        This is an automated Laravel notification (type: {{ $notification_type ?? '—' }}). Sent on {{ $assigned_at }}.
      </p>
    </div>
  </div>

</body>

</html>
