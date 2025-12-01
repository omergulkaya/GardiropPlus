<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|   example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|   https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|   $route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|   $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|   $route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples: my-controller/index -> my_controller/index
|       my-controller/my-method -> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = false;

// API Routes
$route['api/auth/register'] = 'auth/register';
$route['api/auth/login'] = 'auth/login';
$route['api/auth/logout'] = 'auth/logout';
$route['api/auth/me'] = 'auth/me';
$route['api/auth/refresh'] = 'auth/refresh';
$route['api/auth/verify-email/(:any)'] = 'auth/verify_email/$1';
$route['api/auth/forgot-password'] = 'auth/forgot_password';
$route['api/auth/reset-password'] = 'auth/reset_password';

$route['api/clothing-item'] = 'clothingitem/index';
$route['api/clothing-item/(:any)'] = 'clothingitem/get/$1';
$route['api/clothing-item/(:any)/toggle-favorite'] = 'clothingitem/toggle_favorite/$1';
$route['api/clothing-item/batch/create'] = 'clothingitem/batch_create';
$route['api/clothing-item/batch/delete'] = 'clothingitem/batch_delete';
$route['api/clothing-item/batch/update'] = 'clothingitem/batch_update';

$route['api/outfit'] = 'outfit/index';
$route['api/outfit/(:any)'] = 'outfit/get/$1';
$route['api/outfit/(:any)/toggle-favorite'] = 'outfit/toggle_favorite/$1';

$route['api/upload/image'] = 'upload/image';
$route['api/upload/profile-photo'] = 'upload/profile_photo';
$route['api/upload/profile-photo/(:num)'] = 'upload/get_profile_photo/$1';
$route['api/upload/image/(:any)'] = 'upload/delete_image/$1';

// Metadata routes
$route['api/metadata/categories'] = 'metadata/categories';
$route['api/metadata/colors'] = 'metadata/colors';
$route['api/metadata/seasons'] = 'metadata/seasons';
$route['api/metadata/styles'] = 'metadata/styles';
$route['api/metadata/event-types'] = 'metadata/event_types';
$route['api/metadata/all'] = 'metadata/all';

// Health check routes
$route['api/health'] = 'health/index';
$route['api/health/database'] = 'health/database';
$route['api/health/cache'] = 'health/cache';
$route['api/health/disk'] = 'health/disk';
$route['api/health/version'] = 'health/version';
$route['api/health/check'] = 'health/check';

// API Versioning routes (v1)
$route['api/v1/metadata/categories'] = 'metadata/categories';
$route['api/v1/metadata/colors'] = 'metadata/colors';
$route['api/v1/metadata/seasons'] = 'metadata/seasons';
$route['api/v1/metadata/styles'] = 'metadata/styles';
$route['api/v1/metadata/event-types'] = 'metadata/event_types';
$route['api/v1/metadata/all'] = 'metadata/all';

$route['api/v1/auth/register'] = 'auth/register';
$route['api/v1/auth/login'] = 'auth/login';
$route['api/v1/auth/logout'] = 'auth/logout';
$route['api/v1/auth/me'] = 'auth/me';
$route['api/v1/auth/refresh'] = 'auth/refresh';
$route['api/v1/auth/verify-email/(:any)'] = 'auth/verify_email/$1';
$route['api/v1/auth/forgot-password'] = 'auth/forgot_password';
$route['api/v1/auth/reset-password'] = 'auth/reset_password';

$route['api/v1/clothing-item'] = 'clothingitem/index';
$route['api/v1/clothing-item/(:any)'] = 'clothingitem/get/$1';
$route['api/v1/clothing-item/(:any)/toggle-favorite'] = 'clothingitem/toggle_favorite/$1';
$route['api/v1/clothing-item/batch/create'] = 'clothingitem/batch_create';
$route['api/v1/clothing-item/batch/delete'] = 'clothingitem/batch_delete';
$route['api/v1/clothing-item/batch/update'] = 'clothingitem/batch_update';

$route['api/v1/outfit'] = 'outfit/index';
$route['api/v1/outfit/(:any)'] = 'outfit/get/$1';
$route['api/v1/outfit/(:any)/toggle-favorite'] = 'outfit/toggle_favorite/$1';

$route['api/v1/upload/image'] = 'upload/image';
$route['api/v1/upload/profile-photo'] = 'upload/profile_photo';
$route['api/v1/upload/image/(:any)'] = 'upload/delete_image/$1';

$route['api/v1/health'] = 'health/index';
$route['api/v1/health/database'] = 'health/database';
$route['api/v1/health/cache'] = 'health/cache';
$route['api/v1/health/disk'] = 'health/disk';
$route['api/v1/health/version'] = 'health/version';
$route['api/v1/health/check'] = 'health/check';

// Worn Outfits routes
$route['api/worn-outfit'] = 'wornoutfit/index';
$route['api/worn-outfit/(:any)'] = 'wornoutfit/get/$1';
$route['api/worn-outfit/calendar'] = 'wornoutfit/calendar';
$route['api/worn-outfit/statistics'] = 'wornoutfit/statistics';

// Admin Panel routes
$route['admin'] = 'admin/dashboard';
$route['admin/login'] = 'admin/login';
$route['admin/do_login'] = 'admin/do_login';
$route['admin/logout'] = 'admin/logout';
$route['admin/dashboard'] = 'admin/dashboard';
$route['admin/users'] = 'admin/users';
$route['admin/users/(:num)'] = 'admin/user_detail/$1';
$route['admin/clothing'] = 'admin/clothing';
$route['admin/outfits'] = 'admin/outfits';
$route['admin/statistics'] = 'admin/statistics';
$route['admin/settings'] = 'admin/settings';
$route['api/v1/worn-outfit'] = 'wornoutfit/index';
$route['api/v1/worn-outfit/(:any)'] = 'wornoutfit/get/$1';
$route['api/v1/worn-outfit/calendar'] = 'wornoutfit/calendar';
$route['api/v1/worn-outfit/statistics'] = 'wornoutfit/statistics';

// Shopping List routes
$route['api/shopping-list'] = 'shoppinglist/index';
$route['api/shopping-list/(:any)'] = 'shoppinglist/get/$1';
$route['api/shopping-list/(:any)/status'] = 'shoppinglist/update_status/$1';
$route['api/shopping-list/batch/create'] = 'shoppinglist/batch_create';
$route['api/shopping-list/batch/status'] = 'shoppinglist/batch_update_status';
$route['api/shopping-list/batch/delete'] = 'shoppinglist/batch_delete';
$route['api/shopping-list/statistics'] = 'shoppinglist/statistics';
$route['api/v1/shopping-list'] = 'shoppinglist/index';
$route['api/v1/shopping-list/(:any)'] = 'shoppinglist/get/$1';
$route['api/v1/shopping-list/(:any)/status'] = 'shoppinglist/update_status/$1';
$route['api/v1/shopping-list/batch/create'] = 'shoppinglist/batch_create';
$route['api/v1/shopping-list/batch/status'] = 'shoppinglist/batch_update_status';
$route['api/v1/shopping-list/batch/delete'] = 'shoppinglist/batch_delete';
$route['api/v1/shopping-list/statistics'] = 'shoppinglist/statistics';

// Analytics routes
$route['api/analytics/wardrobe'] = 'analytics/wardrobe';
$route['api/analytics/usage'] = 'analytics/usage';
$route['api/analytics/style'] = 'analytics/style';
$route['api/analytics/seasonal'] = 'analytics/seasonal';
$route['api/analytics/maintenance'] = 'analytics/maintenance';
$route['api/v1/analytics/wardrobe'] = 'analytics/wardrobe';
$route['api/v1/analytics/usage'] = 'analytics/usage';
$route['api/v1/analytics/style'] = 'analytics/style';
$route['api/v1/analytics/seasonal'] = 'analytics/seasonal';
$route['api/v1/analytics/maintenance'] = 'analytics/maintenance';

// Social routes
$route['api/social/share'] = 'social/share';
$route['api/social/share/(:any)'] = 'social/unshare/$1';
$route['api/social/shared'] = 'social/shared';
$route['api/social/like'] = 'social/like';
$route['api/social/comment'] = 'social/comment';
$route['api/social/comments/(:any)'] = 'social/comments/$1';
$route['api/social/comment/(:any)'] = 'social/delete_comment/$1';
$route['api/social/follow'] = 'social/follow';
$route['api/social/followers'] = 'social/followers';
$route['api/social/following'] = 'social/following';
$route['api/social/feed'] = 'social/feed';
$route['api/v1/social/share'] = 'social/share';
$route['api/v1/social/share/(:any)'] = 'social/unshare/$1';
$route['api/v1/social/shared'] = 'social/shared';
$route['api/v1/social/like'] = 'social/like';
$route['api/v1/social/comment'] = 'social/comment';
$route['api/v1/social/comments/(:any)'] = 'social/comments/$1';
$route['api/v1/social/comment/(:any)'] = 'social/delete_comment/$1';
$route['api/v1/social/follow'] = 'social/follow';
$route['api/v1/social/followers'] = 'social/followers';
$route['api/v1/social/following'] = 'social/following';
$route['api/v1/social/feed'] = 'social/feed';
