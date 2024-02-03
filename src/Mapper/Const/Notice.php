<?php
declare(strict_types = 1);
/***
 * Date 23.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper\Const;

/**
 * Class Notice
 */
class Notice
{
	/*
	N - Notices
	E = Errors
	W = Warnings
	*/


	public const N_PROFILE_UPDATED 			= 'Profile updated';
	public const N_PRODUCT_PARSED			= 'Product parsed';
	public const N_PARSER_DISABLED 			= 'Parser disabled';
	public const N_PROXIES_DELETED			= 'Proxy successfully deleted';
	public const N_TOOL_DELETED			    = 'Tool successfully deleted';
	public const N_SAVED 					= 'Saved!';
	public const N_TASK_DELETED 			= 'Task deleted';
	public const N_TOOL_ADDED_ALREADY		= 'The tool has already been added';
	public const N_TOOL_ADDED		= 'The tool added';
	public const N_USER_DELETED 			= 'User deleted';
	public const N_USER_ADDED 				= 'User added';
	public const N_CRONTASK_ADDED 				= 'User added';
	public const N_USER_EXIST 				= 'Login already exists';

	public const E_PARSE_CONTENT 			= 'Error parsing content';
    public const E_ADD_DATA 				= 'Error adding data';
    public const E_ADD_NEW_TOOL				= 'Error adding tool';
    public const E_ADD_PROXY				= 'Error adding proxy to database';
    public const E_ADD_CONFIG				= 'Error adding tool config';
    public const E_ADD_USER					= 'Failed to add user';
    public const E_GET_NO_USER				= 'Error getting user';
    public const E_GET_PROFILE 				= 'Error getting profile';
    public const E_GET_USERS 				= 'Error getting users';
    public const E_NO_ACCESS 				= 'Error getting access';
    public const E_NO_USER_DELETE			= 'Failed to delete user';
    public const E_NO_USER_UPDATE 			= 'Failed to update user';
    public const E_PRODUCT_UPDATE			= 'Error updating product status';
    public const E_PROFILE_UPDATE			= 'Profile update error';
    public const E_REQUEST 					= 'Invalid request';
    public const E_CONFIG_INFO				= 'Error of config info';
    public const E_UPDATE_INFO 				= 'Error update info';
	public const E_FAILED_USER_ADD 			= 'Failed to add user';
	public const E_URL						= 'Url error';


    public const W_EMPTY_LOGIN 				= 'Login cant be empty';
    public const W_EMPTY_PASSWORD			= 'Password cant be empty';
    public const W_EMPTY_RESPONSE			= 'Empty response';
    public const W_HACKING_ATTEMPT 			= 'Hacking attempt';
    public const W_INVALID_ACTION 			= 'Invalid action value';
    public const W_INVALID_AUTH				= 'Wrong login or password';
    public const W_INVALID_EMAIL 			= 'Invalid email address';
    public const W_INVALID_FILE				= 'Invalid file format. (Not .csv). Parser disabled.';
    public const W_INVALID_NAME 			= 'Invalid name';
    public const W_INVALID_PARAM 			= 'Invalid parameter';
	public const W_INVALID_PARAMS			= 'Invalid parameters';
    public const W_INVALID_PARAM_VAL 		= 'Invalid parameter value';
    public const W_INVALID_PASSWORD			= 'Invalid password';
    public const W_INVALID_REQUEST 			= 'Invalid request';
    public const W_NAME_NOT_EXCEED_15		= 'Name must not exceed 15 characters';
    public const W_NAME_ONLY_LETTERS 		= 'Name must contain only letters';
    public const W_NO_ACCESS 				= 'Access denied';
    public const W_NO_ACCESS_TO_ADD_USER 	= 'You dont have access for adding users';
    public const W_NO_ACCESS_TO_DELETE 		= 'You dont have access for deleting users';
    public const W_NO_ACCESS_TO_EDIT 		= 'You dont have access for editing users';
    public const W_NO_CATEGORY 				= 'No categories found';
    public const W_NO_DIGIT 				= 'ID should be numeric';
    public const W_NO_PARSER 				= 'No parser found';
    public const W_NO_PRODUCT				= 'No products found';
    public const W_NO_PRODUCT_TO_PARSE 		= 'No products to parse';
    public const W_NO_PROXIES_DELETED 		= 'No proxies deleted';
    public const W_NO_PROXY_TO_DELETE 		= 'No proxies to delete';
    public const W_NO_PROXY_WORKING 		= 'No working proxies. Disable or add working proxies';
    public const W_PASSWORD_NOT_MATCH		= 'Passwords dont match';
    public const W_USER_LOGIN_EMEIL_EXISTS 	= 'User with such email or login already exists';
    public const W_EMAIL_REQUIRED 			= 'Email required';
    public const W_LOGIN_REQUIRED 			= 'Login required';
    public const W_USERNAME_REQUIRED 		= 'Username required';
    public const W_TOOLNAME_REQUIRED 		= 'Tool name required';
	public const E_INVALID_ACTION 			= 'Invalid action';
	public const E_USER_NOT_FOUND 			= 'User not found';
	public const E_TASK_NOT_FOUND 			= 'Cron task not found';











}