<?php namespace Rj;

class HelperHumanize {

	public static function humanizeTimeAgo($datetime) {
		$diff = time() - strtotime($datetime);

		switch (true) {
			case $diff < 30:
				return 'Только что';

			case $diff < 60: // minute
				return 'Менее минуты назад';

			case $diff < 60 * 60: // hour
				return ($i = round($diff / 60)) . ' минут' . Helper::strend($i, 'а,ы,') . ' назад';

			case $diff < 60 * 60 * 24:
				return ($i = round($diff / (60 * 60))) . ' час' . Helper::strend($i, ',а,ов') . ' назад';

			case $diff < 60 * 60 * 24 * 7:
				return ($i = round($diff / (60 * 60 * 24))) . ' ' . Helper::strend($i, 'день,дня,дней') . ' назад';

			default:
				return 'Больше недели назад';
		}
	}

	public static function humanizeTimeEst($datetime) {
		$diff = strtotime($datetime) - time();

		switch (true) {
			case $diff < 0:
				return '';

			case $diff < 60:
				return 'менее минуты';

			case $diff < 60 * 60: // hour
				$i = round($diff / 60);
				return Helper::strend($i, 'Остался,Осталось,Осталось') . ' ' . $i . ' ' . Helper::strend($i, 'минута,минуты,минут');

			case $diff < 60 * 60 * 24:
				$i = round($diff / (60 * 60));
				return Helper::strend($i, 'Остался,Осталось,Осталось') . ' ' . $i . ' ' . Helper::strend($i, 'час,часа,часов');

			case $diff < 60 * 60 * 24 * 7:
				$i = round($diff / (60 * 60 * 24));
				return Helper::strend($i, 'Остался,Осталось,Осталось') . ' ' . $i . ' ' . Helper::strend($i, 'день,дня,дней');

			default:
				return '';
		}
	}

	public static function humanizeFileSize($size, $decimals = 0) {
		$sz = [ 'б', 'Кб', 'Мб', 'Гб', 'Tb', 'Pb' ];
		$factor = intval(floor((strlen($size) - 1) / 3));
		return sprintf("%.{$decimals}f", $size / pow(1024, $factor)) . @$sz[$factor];
	}

}
