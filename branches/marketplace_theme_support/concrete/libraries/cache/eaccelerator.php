<?

class Cache extends CacheTemplate {

	/** 
	 * Completely flushes the cache
	 */
	public function flush() {
		eaccelerator_clear();
	}
	
	/** 
	 * Removes an item from the cache
	 */
	public function delete($type, $id) {
		
		if (ENABLE_CACHE == false) {
			return false;
		}
		
		/*
		$l = new Log();
		$l->write('Deleting Cache for ' . $type . ' ' . $id);
		*/
		
		$k = parent::key($type, $id);
		$result = eaccelerator_rm($k);
		if ($result) {
			$loc = CacheLocal::get();
			unset($loc->cache[$k]);
		}
		return $result;
	}
	
	/** 
	 * Inserts or updates an item to the cache
	 */
	public function set($type, $id, $obj, $expire = 0) {
		if (ENABLE_CACHE == false) {
			return false;
		}
		
		/*
		$l = new Log();
		$l->write('Setting Cache for ' . $type . ' ' . $id);
		*/
		
		$k = parent::key($type, $id);
		$s = serialize($obj);
		$r = eaccelerator_put($k, $s, $expire);
		if ($r) {
			$loc = CacheLocal::get();
			$loc->cache[$k] = $obj;
		}
	}
	
	/** 
	 * Retrieves an item from the cache
	 */
	public function get($type, $id) {
		
		if (ENABLE_CACHE == false) {
			return false;
		}
		
		$k = parent::key($type, $id);
		$loc = CacheLocal::get();

		if (isset($loc->cache[$k])) {
			$value = $loc->cache[$k];
		} else {
			$s = eaccelerator_get($k);
			$value = unserialize($s);
		}
		
		/*
		$l = new Log();
		$l->write('Getting Cache for ' . $type . ' ' . $id);
		*/
		
		if ($value === NULL) {
			$value = false;
		}
		
		$loc->cache[$k] = $value;
		return $value;
	}
	
	/** 
	 * Retrieves information about the cache
	 */
	public function stats() {
		print_r(eaccelerator_info());
	}
}


