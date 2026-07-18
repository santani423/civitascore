<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Controllers\PermissionController;
use Modules\UserManagement\Controllers\RoleController;
use Modules\UserManagement\Controllers\UserRoleController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.read');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
    Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('permission:roles.read');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');
    Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:roles.update');

    Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.read');
    Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create');
    Route::get('permissions/{permission}', [PermissionController::class, 'show'])->middleware('permission:permissions.read');
    Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update');
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete');

    Route::get('users/{user}/roles', [UserRoleController::class, 'index'])->middleware('permission:user_roles.read');
    Route::post('users/{user}/roles', [UserRoleController::class, 'store'])->middleware('permission:user_roles.create');
    Route::delete('users/{user}/roles/{role}', [UserRoleController::class, 'destroy'])->middleware('permission:user_roles.delete');
});
