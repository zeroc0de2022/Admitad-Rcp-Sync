<?php
declare(strict_types = 1);
/***
 * Date 25.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper\Const;

/**
 * Class Constants
 * @package Cpsync\Mapper\Const
 */
class Constants
{
	public const TOOLS_COLUMNS = ['tool_id', 'tool', 'tool_name', 'remote_link', 'reg_time'];
	public const TOOLS_CONFIG = [
		'up_sec' => 5,           // Speed of parsing when manually started
		'thread' => 1,           // Number of threads
		'proxy_status' => 0,     // Proxy status
		'error_count' => 5,      // Number of allowed errors when parsing
		'csv_status' => 0,       // CSV parsing status
		'product_status' => 0,   // Product parsing status
		'limit_parsed_line' => 0 //Number of products for unloading
	];
	public const TOOL_DEFAULT_INFO = [
		'csv_notice' => 'ok',           // Notice about csv parser
		'product_notice' => 'ok',       // Notice about product parser
		'csv_upload_time' => 0,         // Upload time
		'csv_upload_size' => 0,         // Upload size
		'csv_product_sum' => 0,         // Upload product sum
		'csv_new_product_sum' => 0,     // Upload new product sum
		'csv_updated_product_sum' => 0, // Upload updated product sum
		'csv_parsed_product_sum' => 0,  // Upload parsed product sum
		'all_product_sum' => 0          // All product sum
	];
	public const IMPORTER_DEFAULT_INFO = ['csv_upload_time' => 0,         // Sync date/time
		'csv_product_sum' => 0,         // Sum of products on the site
		'csv_new_product_sum' => 0,     // Sum of new products in the sync
		'csv_updated_product_sum' => 0, // Sum of upd products in the sync
		'all_product_sum' => 0,         // All product sum
		'hdd_size' => 0,                // HDD filled size
		'hdd_free' => 0,                // HDD free size
		'dbsize' => 0                   // DB size
	];

	public const FIELDS = ['product_id', 'available', 'categoryId', 'currencyId', 'model', 'modified_time', 'name', 'picture',  'price', 'typePrefix', 'url', 'vendor',  'admitad', 'images', 'description', 'attrs', 'reviews'];

	public const COLUMNS = ['available', 'categoryId', 'currencyId', 'product_id', 'model','modified_time', 'name', 'picture', 'price', 'typePrefix', 'vendor', 'url'];


}