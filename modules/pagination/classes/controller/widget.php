<?php

namespace Pagination;


class Controller_Widget extends \Controller_Template
{

	/**
	 * Renders the pagination widget
	 *
	 * Usage:
	 *
	 *     $params = array(
	 *         'pagination_url'  => '/list/',
	 *         'total_items'     => \Model_List::find()->count(),
	 *         'per_page'        => 5,
	 *         'num_links'       => 10,    // optional
	 *         'current_page'    => $page,
	 *     );
	 *     $data = \Model_List::find()
	 *         ->limit($params['per_page'])
	 *         ->offset(($page - 1) * $params['per_page'])
	 *         ->get();
	 *     $pagination = \Request::forge('pagination/widget/render', false)->execute(array($params));
	 *
	 * @param   array   $params  Parameters
	 * @return  string
	 */
	public function action_render($params)
	{
		if (\Request::main() === \Request::active())
		{
			$this->template = \View::forge('404');
			$this->response->status = 404;
		}

		$this->template->total_pages = ceil($params['total_items'] / $params['per_page']) ?: 1;

		if ($this->template->total_pages === 1)
		{
			return '';
		}

		$this->template->current_page = (int) $params['current_page'];

		if ($this->template->current_page > $this->template->total_pages)
		{
			$this->template->current_page = $this->template->total_pages;
		}
		elseif ($this->template->current_page < 1)
		{
			$this->template->current_page = 1;
		}

		\Config::load('pagination', true);
		\Lang::load('pagination', true);

		$this->template->pagination_url = $params['pagination_url'];
		$this->template->num_links = isset($params['num_links']) ? $params['num_links'] : \Config::get('pagination.num_links');
	}

}
