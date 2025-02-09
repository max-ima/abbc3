<?php
/**
 *
 * Advanced BBCode Box
 *
 * @copyright (c) 2014 Matt Friedman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\abbc3\tests\controller;

class wizard_test extends \phpbb_test_case
{
	/** @var \ReflectionClass */
	protected static $reflection_method_load_json_data;

	/** @var \vse\abbc3\controller\wizard */
	protected $controller;

	/** @var \phpbb\request\request|\PHPUnit\Framework\MockObject\MockObject */
	protected $request;

	/** @var \phpbb\template\template|\PHPUnit\Framework\MockObject\MockObject */
	protected $template;

	/** @var \phpbb\textformatter\s9e\factory $factory */
	protected $textformatter;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();

		/** @var $controller_helper \phpbb\controller\helper|\PHPUnit\Framework\MockObject\MockObject */
		$controller_helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		$controller_helper->expects(self::atMost(1))
			->method('render')
			->willReturnCallback(function ($template_file, $page_title = '', $status_code = 200) {
				return new \Symfony\Component\HttpFoundation\Response($template_file, $status_code);
			});

		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();

		$container = $this->get_test_case_helpers()->set_s9e_services();
		$this->textformatter = $container->get('text_formatter.s9e.factory');

		$this->controller = new \vse\abbc3\controller\wizard(
			new \phpbb_mock_cache,
			$controller_helper,
			$this->request,
			$this->template,
			$this->textformatter
		);
	}

	public function bbcode_wizard_data()
	{
		return [
			['bbvideo', true, 200, 'abbc3_bbvideo_wizard.html'],
			['pipes', true, 200, 'abbc3_pipes_wizard.html'],
			['url', true, 200, 'abbc3_url_wizard.html'],
		];
	}

	/**
	 * @dataProvider bbcode_wizard_data
	 * @param $mode
	 * @param $ajax
	 * @param $status_code
	 * @param $page_content
	 */
	public function test_bbcode_wizard($mode, $ajax, $status_code, $page_content)
	{
		$this->request->expects(self::once())
			->method('is_ajax')
			->willReturn($ajax)
		;

		$response = $this->controller->bbcode_wizard($mode);
		self::assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		self::assertEquals($status_code, $response->getStatusCode());
		self::assertEquals($page_content, $response->getContent());
	}

	public function bbcode_wizard_fails_data()
	{
		return [
			['bbvideo', false],
			['pipes', false],
			['url', false],
			['foobars', true],
			['foobars', false],
			['', true],
			['', false],
		];
	}

	/**
	 * Test the controller throws an exception on erroneous calls
	 *
	 * @dataProvider             bbcode_wizard_fails_data
	 * @param $mode
	 * @param $ajax
	 */
	public function test_bbcode_wizard_fails($mode, $ajax)
	{
		$this->expectExceptionMessage('GENERAL_ERROR');
		$this->expectException(\phpbb\exception\http_exception::class);
		$this->request->expects(self::once())
			->method('is_ajax')
			->willReturn($ajax);

		$this->controller->bbcode_wizard($mode);
	}

	/**
	 * Test generate_bbvideo_wizard works
	 */
	public function test_generate_bbvideo_wizard()
	{
		$this->template->expects(self::once())
			->method('assign_vars')
			->with([
				'ABBC3_BBVIDEO_SITES'   => [],
				'ABBC3_BBVIDEO_DEFAULT' => \vse\abbc3\controller\wizard::BBVIDEO_DEFAULT,
			]);

		try
		{
			$this->invokeMethod($this->controller, 'generate_bbvideo_wizard');
		}
		catch (\ReflectionException $e)
		{
			self::fail($e->getMessage());
		}
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @param \vse\abbc3\controller\wizard &$object     Instantiated object that we will run method on.
	 * @param string                        $methodName Method name to call
	 * @param array                         $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 * @throws \ReflectionException
	 */
	public function invokeMethod(&$object, $methodName, array $parameters = [])
	{
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}
}
