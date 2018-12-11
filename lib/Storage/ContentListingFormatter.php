<?php
/**
 * @author Hemant Mann <hemant.mann121@gmail.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace OCA\Files_external_dropbox\Storage;

use League\Flysystem\Util;
use League\Flysystem\Util\ContentListingFormatter as FlysystemContentListingFormatter;

class ContentListingFormatter extends FlysystemContentListingFormatter {
	/**
	 * @var string
	 */
	private $directory;
	/**
	 * @var bool
	 */
	private $recursive;

	/**
	 * @var bool
	 */
	protected $isCaseInsensitiveStorage = false;

	/**
	 * @param string $directory
	 * @param bool   $recursive
	 */
	public function __construct($directory, $recursive) {
		$this->directory = $directory;
		$this->recursive = $recursive;
	}

	/**
	 * @param bool $value
	 */
	public function setIsCaseInsensitiveStorage($value) {
		$this->isCaseInsensitiveStorage = $value;
	}

	/**
	 * This method not part of third party library
	 * If the storage is case insensitive then path should be converted to lowercase
	 * @param  string $path Path to file/folder
	 * @return string       Modified Path
	 */
	public function formatPath($path) {
		if ($this->isCaseInsensitiveStorage) {
			$path = \strtolower($path);
		}
		return $path;
	}

	/**
	 * Format contents listing.
	 *
	 * @param array $listing
	 *
	 * @return array
	 */
	public function formatListing(array $listing) {
		$listing = \array_values(
			\array_map(
				[$this, 'addPathInfo'],
				\array_filter($listing, [$this, 'isEntryOutOfScope'])
			)
		);

		return $this->sortListing($listing);
	}

	private function addPathInfo(array $entry) {
		return $entry + Util::pathinfo($entry['path']);
	}

	/**
	 * Determine if the entry is out of scope.
	 *
	 * @param array $entry
	 *
	 * @return bool
	 */
	private function isEntryOutOfScope(array $entry) {
		if (empty($entry['path']) && $entry['path'] !== '0') {
			return false;
		}

		if ($this->recursive) {
			return $this->residesInDirectory($entry);
		}

		return $this->isDirectChild($entry);
	}

	/**
	 * Check if the entry resides within the parent directory.
	 * Update path comparison check using $this->formatPath
	 * @param $entry
	 *
	 * @return bool
	 */
	private function residesInDirectory(array $entry) {
		if ($this->directory === '') {
			return true;
		}

		return \strpos($this->formatPath($entry['path']), $this->formatPath($this->directory) . '/') === 0;
	}

	/**
	 * Check if the entry is a direct child of the directory.
	 * Update path comparision check using $this->formatPath
	 * @param $entry
	 *
	 * @return bool
	 */
	private function isDirectChild(array $entry) {
		return Util::dirname($this->formatPath($entry['path'])) === $this->formatPath($this->directory);
	}

	/**
	 * @param array $listing
	 *
	 * @return array
	 */
	private function sortListing(array $listing) {
		\usort(
			$listing,
			function ($a, $b) {
				return \strcasecmp($a['path'], $b['path']);
			}
		);

		return $listing;
	}
}
