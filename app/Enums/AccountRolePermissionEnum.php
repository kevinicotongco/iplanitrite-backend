<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountRolePermissionEnum: string
{
    case StaffList = 'StaffList';
    case StaffCreate = 'StaffCreate';
    case StaffUpdate = 'StaffUpdate';
    case StaffDelete = 'StaffDelete';
    case EventList = 'EventList';
    case EventCreate = 'EventCreate';
    case EventUpdate = 'EventUpdate';
    case EventDelete = 'EventDelete';
    case BankDetailList = 'BankDetailList';
    case BankDetailCreate = 'BankDetailCreate';
    case BankDetailUpdate = 'BankDetailUpdate';
    case BankDetailDelete = 'BankDetailDelete';
}
