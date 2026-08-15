<?php

namespace App\Console\Commands;

use App\Support\MobileNotificationDispatcher;
use Illuminate\Console\Command;

class DispatchMobileNotifications extends Command
{
  protected $signature = 'mobile:dispatch-notifications {type? : check_in|check_out|expense|outlet|events|all}';

  protected $description = 'Generate in-app mobile notifications from live business state.';

  public function handle(): int
  {
    $type = $this->argument('type') ?: 'all';
    $dispatcher = new MobileNotificationDispatcher();
    $created = 0;

    if (in_array($type, ['check_in', 'all'], true)) {
      $created += $dispatcher->dispatchCheckInReminders();
    }
    if (in_array($type, ['check_out', 'all'], true)) {
      $created += $dispatcher->dispatchCheckOutReminders();
    }
    if (in_array($type, ['expense', 'all'], true)) {
      $created += $dispatcher->dispatchExpenseReminders();
    }
    if (in_array($type, ['outlet', 'all'], true)) {
      $created += $dispatcher->dispatchOutletPending();
    }
    if (in_array($type, ['events', 'all'], true)) {
      $created += $dispatcher->dispatchActiveEvents();
    }

    $this->info('Created ' . $created . ' notification(s).');

    return self::SUCCESS;
  }
}
