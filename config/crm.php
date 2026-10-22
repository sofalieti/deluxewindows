<?php

declare(strict_types=1);

return [
    'new_lead_task_minutes' => (int) env('CRM_NEW_LEAD_TASK_MINUTES', 15),
    'no_call_task_minutes' => (int) env('CRM_NO_CALL_TASK_MINUTES', 30),
    'missed_call_task_minutes' => (int) env('CRM_MISSED_CALL_TASK_MINUTES', 15),
];
