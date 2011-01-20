<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once ('pages/abstract/IDBCachable.php');

class FunStatistics extends Page implements IDBCachable {

	private static $barColors = array();
	private static $barColorArray = array(
		'8B0000',
		'FF8800',
		'006400'
	);

	public function prepare() {
		$this->setValue('title', $this->L10n->getText('Fun statistics'));
		if (!($body = $this->PersistentCache->getObject('FunStatistics:' . $this->L10n->getLocale()))) {
			$this->Output->setStatus(Output::NOT_FOUND);
			$this->showFailure($this->L10n->getText('No data found!'));
		}
		$this->setValue('body', $body);
	}

	public static function updateDBCache() {
		self::$barColors = self::MultiColorFade(self::$barColorArray);
		try {
			$body = '<div class="box">
			<table id="packagedetails">
				<tr>
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('Browser') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'Mozilla Firefox' => 'firefox',
				'Chromium' => 'chromium',
				'Konqueror' => 'kdebase-konqueror',
				'Midori' => 'midori',
				'Arora' => 'arora',
				'Epiphany' => 'epiphany',
				'Rekonq' => 'rekonq',
				'Uzbl' => 'uzbl-core',
				'Netsurf' => 'netsurf',
				'Dillo' => 'dillo'
			)) . '
				<tr>
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('Editors') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'Vim' => array(
					'vim',
					'gvim'
				) ,
				'Emacs' => array(
					'emacs',
					'xemacs'
				) ,
				'Nano' => 'nano',
				'Gedit' => 'gedit',
				'Kate' => 'kdesdk-kate',
				'Kwrite' => 'kdebase-kwrite',
				'Jedit' => 'jedit',
				'Ne' => 'ne',
				'Jed' => 'jed',
				'Medit' => 'medit',
				'Vi' => 'vi',
				'Mousepad' => 'mousepad',
				'Medit' => 'nedit',
				'Joe' => 'joe'
			)) . '
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('Desktop Environments') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'KDE SC' => 'kdebase-workspace',
				'GNOME' => 'gnome-session',
				'LXDE' => 'lxde-common',
				'Xfce' => 'xfdesktop',
				'e17' => 'e-svn'
			)) . '
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('File Managers') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'Dolphin' => 'kdebase-dolphin',
				'Konqueror' => 'kdebase-konqueror',
				'MC' => 'mc',
				'Nautilus' => 'nautilus',
				'Gnome-Commander' => 'gnome-commander',
				'Krusader' => 'krusader',
				'Pcmanfm' => 'pcmanfm',
				'Thunar' => 'thunar'
			)) . '
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('Window Managers') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'Openbox' => 'openbox',
				'Fluxbox' => 'fluxbox',
				'I3' => 'i3-wm',
				'Compiz' => 'compiz-core',
				'FVWM' => array(
					'fvwm',
					'fvwm-devel'
				) ,
				'Ratpoison' => 'ratpoison',
				'Wmii' => 'wmii',
				'Xmonad' => 'xmonad',
				'Window Maker' => array(
					'windowmaker',
					'windowmaker-crm-git'
				)
			)) . '
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('Media Players') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'Mplayer' => 'mplayer',
				'Xine' => 'xine-lib',
				'VLC' => 'vlc'
			)) . '
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('Shells') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'Bash' => 'bash',
				'Dash' => 'dash',
				'Zsh' => 'zsh',
				'Fish' => 'fish',
				'Tcsh' => 'tcsh',
				'Pdksh' => 'pdksh'
			)) . '
					<th colspan="2" class="packagedetailshead">' . self::get('L10n')->getText('Graphic Chipsets') . '</th>
				</tr>
					' . self::getPackageStatistics(array(
				'ATI' => array(
					'xf86-video-ati',
					'xf86-video-r128',
					'xf86-video-mach64',
					'xf86-video-radeonhd'
				) ,
				'NVIDIA' => array(
					'nvidia-utils',
					'nvidia-96xx-utils',
					'nvidia-173xx-utils',
					'xf86-video-nouveau',
					'xf86-video-nv'
				) ,
				'Intel' => array(
					'xf86-video-intel',
					'xf86-video-i740'
				)
			)) . '
			</table>
			</div>
			';
			self::get('PersistentCache')->addObject('FunStatistics:' . self::get('L10n')->getLocale() , $body);
		} catch(DBNoDataException $e) {
		}
	}

	private static function getPackageStatistics($packages) {
		$total = DB::query('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		')->fetchColumn();
		$stm = DB::prepare('
		SELECT
			SUM(count)
		FROM
			pkgstats_packages
		WHERE
			pkgname = :pkgname
		GROUP BY
			pkgname
		');
		$packageArray = array();
		$list = '';
		foreach ($packages as $package => $pkgnames) {
			if (!is_array($pkgnames)) {
				$pkgnames = array(
					$pkgnames
				);
			}
			foreach ($pkgnames as $pkgname) {
				$stm->bindValue('pkgname', htmlspecialchars($pkgname), PDO::PARAM_STR);
				$stm->execute();
				$count = $stm->fetchColumn() ?: 0;
				if (isset($packageArray[htmlspecialchars($package) ])) {
					$packageArray[htmlspecialchars($package) ]+= $count;
				} else {
					$packageArray[htmlspecialchars($package) ] = $count;
				}
			}
		}
		arsort($packageArray);
		foreach ($packageArray as $name => $count) {
			$list.= '<tr><th>' . $name . '</th><td>' . self::getBar($count, $total) . '</td></tr>';
		}
		return $list;
	}

	private static function getBar($value, $total) {
		if ($total <= 0) {
			return '';
		}
		$percent = ($value / $total) * 100;
		$color = self::$barColors[round($percent) ];
		return '<table style="width:100%;">
			<tr>
				<td style="padding:0px;margin:0px;">
					<div style="background-color:#' . $color . ';width:' . round($percent) . '%;"
		title="'.self::get('L10n')->getNumber($value).' '.self::get('L10n')->getText('of').' '.self::get('L10n')->getNumber($total).'">
			&nbsp;
				</div>
				</td>
				<td style="padding:0px;margin:0px;width:80px;text-align:right;color:#' . $color . '">
					' . self::get('L10n')->getNumber($percent, 2) . '&nbsp;%
				</td>
			</tr>
		</table>';
	}

	// see http://at.php.net/manual/de/function.hexdec.php#66780
	private static function MultiColorFade($hexarray) {
		$steps = 101;
		$total = count($hexarray);
		$gradient = array();
		$fixend = 2;
		$passages = $total - 1;
		$stepsforpassage = floor($steps / $passages);
		$stepsremain = $steps - ($stepsforpassage * $passages);
		for ($pointer = 0;$pointer < $total - 1;$pointer++) {
			$hexstart = $hexarray[$pointer];
			$hexend = $hexarray[$pointer + 1];
			if ($stepsremain > 0) {
				if ($stepsremain--) {
					$stepsforthis = $stepsforpassage + 1;
				}
			} else {
				$stepsforthis = $stepsforpassage;
			}
			if ($pointer > 0) {
				$fixend = 1;
			}
			$start['r'] = hexdec(substr($hexstart, 0, 2));
			$start['g'] = hexdec(substr($hexstart, 2, 2));
			$start['b'] = hexdec(substr($hexstart, 4, 2));
			$end['r'] = hexdec(substr($hexend, 0, 2));
			$end['g'] = hexdec(substr($hexend, 2, 2));
			$end['b'] = hexdec(substr($hexend, 4, 2));
			$step['r'] = ($start['r'] - $end['r']) / ($stepsforthis);
			$step['g'] = ($start['g'] - $end['g']) / ($stepsforthis);
			$step['b'] = ($start['b'] - $end['b']) / ($stepsforthis);
			for ($i = 0;$i <= $stepsforthis - $fixend;$i++) {
				$rgb['r'] = floor($start['r'] - ($step['r'] * $i));
				$rgb['g'] = floor($start['g'] - ($step['g'] * $i));
				$rgb['b'] = floor($start['b'] - ($step['b'] * $i));
				$hex['r'] = sprintf('%02x', ($rgb['r']));
				$hex['g'] = sprintf('%02x', ($rgb['g']));
				$hex['b'] = sprintf('%02x', ($rgb['b']));
				$gradient[] = strtoupper(implode(NULL, $hex));
			}
		}
		$gradient[] = $hexarray[$total - 1];
		return $gradient;
	}
}

?>
