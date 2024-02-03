<?php
declare(strict_types = 1);
/***
 * Date 24.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Parser\phpParser;

use Cpsync\Parser\phpQuery;
use function Cpsync\Parser\pq;

/**
 * Class ContentParser
 * @package Cpsync\Parser\phpParser *
 */
class ContentParser
{
	/**
	 * Product content
	 * @var array $content
	 */
	private array $content = [];

	/**
	 * Product ID
	 * @var int $productId
	 */
	private int $productId;

	/**
	 * Init primary data
	 * @param array $row
	 * @return void
	 */
	public function init(array $row): void
	{
		$this->productId = $row['product_id'];
		$this->content = ['link' => $row['url'], 'picture' => $row['picture'], 'attrs' => [], 'reviews' => [], 'images' => []];
	}

	/***
	 * Parse product data
	 * @param int $handle_id
	 * @param string $html_code - html code
	 * @return array
	 * @throws \Exception
	 */
	public function parseData(int $handle_id, string $html_code): array
	{
		$result_list = [$this->productId => ['status' => false, 'code' => 404, 'bind_values' => [], 'message' => '404 - Product not found', 'content' => [],]];
		// If product found
		if(!str_contains($html_code, 'error_404')) {
			// Parse html code
			$document = phpQuery::newDocument($html_code);
			$content = $document->find('div#content');
			$content = pq($content);
			// Product description
			$this->content['description'] = trim($content->find('div.cr-info-descr')->html());
			// Product images
			$this->parseImages($content);
			// Product attributes
			$this->parseAttrs($content);
			// Parse product reviews
			$this->parseReviews($content);

			$result_list[$handle_id]['status'] = true;
			$result_list[$handle_id]['code'] = 200;
			$result_list[$handle_id]['message'] = '200 - ok';
			$result_list[$handle_id]['content'] = $this->content;
			$result_list[$handle_id]['bind_values'][':product_id'] = $handle_id;
			$result_list[$handle_id]['bind_values'][':description'] = $this->content['description'];
			$result_list[$handle_id]['bind_values'][':images'] = json_encode($this->content['images']);
			$result_list[$handle_id]['bind_values'][':attrs'] = json_encode($this->content['attrs']);
			$result_list[$handle_id]['bind_values'][':reviews'] = json_encode($this->content['reviews']);
		}
		$result_list[$handle_id]['status'] = $this->checkContent();
		return $result_list;
	}


	/***
	 * Parse product reviews
	 * @param mixed $content - phpQuery object
	 * @return void
	 */
	private function parseReviews(mixed $content): void
	{
		$reviews = [];
		foreach($content->find('li[id^=review-]') as $reviews_item) {
			$reviews_item = pq($reviews_item);
			$reviews_item->find('.cr-moderator_comment')->empty();
			$reviews[] = ['rating' => trim($reviews_item->find('meta[itemprop=ratingValue]')->attr('content')), 'author' => trim($reviews_item->find('span[itemprop=author]')->text()), 'review' => trim($reviews_item->find('span[itemprop=description]')->text()), 'date' => trim($reviews_item->find('span.reviews__date')->text()), 'plus' => $this->getReviewText($reviews_item, 1), 'minus' => $this->getReviewText($reviews_item, 2),];
		}
		$this->content['reviews'] = $reviews;
	}

	/***
	 * Get product review
	 * @param object $reviews_item - phpQuery object
	 * @param int $index - index of review
	 * @return string
	 */
	private function getReviewText(object $reviews_item, int $index): string
	{
		$trans = ['Плюсы:' => '', 'Минусы:' => ''];
		$body_eq = $reviews_item->find("span.reviews__body:eq($index)")->text();
		return trim(strtr($body_eq, $trans));
	}


	/***
	 * Parse product attrs
	 * @param mixed $content - phpQuery object
	 * @return void
	 */
	private function parseAttrs(mixed $content): void
	{
		foreach($content->find('div.cr-info-attrs div.attr_item') as $attr_item) {
			$attr_item = pq($attr_item);
			$this->content['attrs'][] = ['name' => trim($attr_item->find('span.attr__name')->text()), 'value' => trim($attr_item->find('span.attr__value')->text())];
		}
	}

	/***
	 * Parse product images
	 * @param mixed $content - phpQuery object
	 * @return void
	 */
	private function parseImages(mixed $content): void
	{
		$images = [];
		foreach($content->find('div.b-photo img[itemprop=image]') as $photo) {
			$photo = pq($photo);
			$images[] = str_replace('/preview_b', '', $photo->attr('src'));
		}
		$img_key = array_search($this->content['picture'], $images);
		if($img_key !== false) {
			unset($images[$img_key]);
			$images = array_values($images);
		}
		$this->content['images'] = $images;
	}

	/***
	 * Check if product has content
	 * @return bool
	 */
	private function checkContent(): bool
	{
		return (is_array($this->content['attrs']) || is_array($this->content['reviews']) || is_array($this->content['images']) || strlen($this->content['description']));
	}
}