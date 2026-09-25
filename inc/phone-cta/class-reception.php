<?php
/**
 * 受付時間の判定。WordPress に依存しない純粋関数。
 *
 * $shop は API の店舗 JSON（連想配列）:
 *   is_always_open: bool
 *   hours: [ { wday: 0-6, start: "HH:MM", end: "HH:MM" }, ... ]
 * 行が無い曜日は休み。end <= start は日跨ぎ（例 22:00〜02:00）。
 */
class Madoguchi_Blocks_Phone_Cta_Reception {

	public static function now_jst(): DateTimeImmutable {
		return new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Tokyo' ) );
	}

	public static function is_open( array $shop, DateTimeImmutable $now ): bool {
		if ( ! empty( $shop['is_always_open'] ) ) {
			return true;
		}
		$rows = isset( $shop['hours'] ) && is_array( $shop['hours'] ) ? $shop['hours'] : array();
		$now  = $now->setTimezone( new DateTimeZone( 'Asia/Tokyo' ) );

		$today     = (int) $now->format( 'w' );
		$yesterday = ( $today + 6 ) % 7;
		$minutes   = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );

		foreach ( $rows as $row ) {
			$parsed = self::parse_row( $row );
			if ( null === $parsed ) {
				continue;
			}
			list( $wday, $start, $end ) = $parsed;

			if ( $wday === $today ) {
				if ( $start < $end ) {
					if ( $minutes >= $start && $minutes < $end ) {
						return true;
					}
				} elseif ( $minutes >= $start ) { // 日跨ぎの当日側（22:00〜24:00）
					return true;
				}
			}
			// 前日の行が日跨ぎなら、その早朝側（00:00〜end）
			if ( $wday === $yesterday && $end <= $start && $minutes < $end ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array{0:int,1:int,2:int}|null [wday, start_minutes, end_minutes]
	 */
	private static function parse_row( $row ): ?array {
		if ( ! is_array( $row ) || ! isset( $row['wday'], $row['start'], $row['end'] ) ) {
			return null;
		}
		$start = self::to_minutes( (string) $row['start'] );
		$end   = self::to_minutes( (string) $row['end'] );
		if ( null === $start || null === $end ) {
			return null;
		}
		$wday = (int) $row['wday'];
		if ( $wday < 0 || $wday > 6 ) {
			return null;
		}
		return array( $wday, $start, $end );
	}

	private static function to_minutes( string $hhmm ): ?int {
		if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', $hhmm, $m ) ) {
			return null;
		}
		$h = (int) $m[1];
		$i = (int) $m[2];
		if ( $h > 24 || $i > 59 ) {
			return null;
		}
		return $h * 60 + $i;
	}
}
