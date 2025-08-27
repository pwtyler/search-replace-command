<?php

namespace WP_CLI;

use ArrayObject;
use Exception;

class URLSearchReplacer extends SearchReplacer {
	/**
	 * @param string  $from             String we're looking to replace.
	 * @param string  $to               What we want it to be replaced with.
	 * @param bool    $recurse_json     Whether to recurse JSON objects
	 */
	public function __construct( $from, $to, $recurse_json=true  ) {
		parent::__construct($from, $to, $recurse_json);
	}

	/**
	 * Runs search-replace, with extra handling for URLs in JSON arrays/objects.
	 *
	 * @param mixed $value The value to search and replace in.
	 * @return mixed The replaced value.
	 */
	public function run($value) {
		// First, run the standard replacement
		$replaced = parent::run($value);

		if (! is_string($replaced)) {
            return $replaced;
        }

        // If the value is a string, check for JSON arrays/objects containing URLs
        return $this->run_replace_escaped_json($value);
	}

    private function run_replace_escaped_json($value) {
        $json = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $changed = false;
            $json = $this->replace_urls_in_json($json, $changed);
            if ($changed) {
                $replaced = json_encode($json);
            }
        }
        return $replaced;
    }

	/**
	 * Recursively replace URLs in a JSON array/object.
	 *
	 * @param mixed $data The JSON data (array/object or value).
	 * @param bool &$changed Set to true if any replacements were made.
	 * @return mixed The replaced data.
	 */
	private function replace_urls_in_json($data, &$changed) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->replace_urls_in_json($value, $changed);
			}
		} elseif (is_object($data)) {
			foreach ($data as $key => $value) {
				$data->$key = $this->replace_urls_in_json($value, $changed);
			}
		} elseif (is_string($data)) {
            $new = parent::run($data);
            if ($new !== $data) {
                $changed = true;
                $data = $new;
            }
		}
		return $data;
	}
}