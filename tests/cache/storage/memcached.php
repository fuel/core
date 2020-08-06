<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Cache_Storage_Memcached class tests
 *
 * @group Core
 * @group Cache
 */
class Test_Cache_Storage_Memcached extends TestCase
{
	 public function test_foo() {}

	 public function test_delete_all()
	 {
 		 \Config::set('cache.memcached.cache_id', '');

		 \Cache::set('section_name.item', 'value');
		 $a = \Cache::get('section_name.item'); // 'value'


		 \Cache::delete_all(); // expected to remove everything

		 $b = 'unchanged';
		 try{
			 $b = \Cache::get('section_name.item');
		 } catch(\CacheNotFoundException $e){
			 $b = 'error';
		 }

		 $this->assertEquals($a, 'value');
		 $this->assertEquals($b, 'error');
	 }

	 public function test_delete_all_with_cache_id()
	 {
 		 \Config::set('cache.memcached.cache_id', 'test');

		 \Cache::set('section_name.item', 'value');
		 $a = \Cache::get('section_name.item'); // 'value'


		 \Cache::delete_all(); // expected to remove everything

		 $b = 'unchanged';
		 try{
			 $b = \Cache::get('section_name.item');
		 } catch(\CacheNotFoundException $e){
			 $b = 'error';
		 }

		 $this->assertEquals($a, 'value');
		 $this->assertEquals($b, 'error');
	 }

	 public function test_delete_all_with_section()
	 {
 		 \Config::set('cache.memcached.cache_id', '');

		 \Cache::set('section_name.item', 'value');
		 $a = \Cache::get('section_name.item'); // 'value'


		 \Cache::delete_all('section_name');

		 $b = 'unchanged';
		 try{
			 $b = \Cache::get('section_name.item');
		 } catch(\CacheNotFoundException $e){
			 $b = 'error';
		 }

		 $this->assertEquals($a, 'value');
		 $this->assertEquals($b, 'error');
	 }

	 public function test_delete_all_with_section_with_cache_id()
	 {

		 \Config::set('cache.memcached.cache_id', 'test');

		 \Cache::set('section_name.item', 'value');
		 $a = \Cache::get('section_name.item'); // 'value'


		 \Cache::delete_all('section_name');

		 $b = 'unchanged';
		 try{
			 $b = \Cache::get('section_name.item');
		 } catch(\CacheNotFoundException $e){
			 $b = 'error';
		 }

		 $this->assertEquals($a, 'value');
		 $this->assertEquals($b, 'error');
	 }
}
