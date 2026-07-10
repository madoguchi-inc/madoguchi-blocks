/**
 * チェックリスト型CTA — フロント用スクリプト
 * 記事側でチェック項目をクリック/タップすると ✓ をトグルする。
 */
( function() {
	// 比較テーブル: 実際に横スクロールが発生するときだけヒントを表示する
	function initScrollHints() {
		var scrolls = document.querySelectorAll( '.comparison-table__scroll' );
		scrolls.forEach( function( el ) {
			var check = function() {
				var scrollable = el.scrollWidth > el.clientWidth + 1;
				el.closest( '.comparison-table' ).classList.toggle( 'is-scrollable', scrollable );
			};
			check();
			window.addEventListener( 'resize', check );
		});
	}

	function init() {
		initScrollHints();
		var items = document.querySelectorAll( '.checklist-cta__list li' );
		items.forEach( function( li ) {
			li.setAttribute( 'role', 'button' );
			li.setAttribute( 'tabindex', '0' );
			li.setAttribute( 'aria-pressed', 'false' );

			function toggle() {
				var checked = li.classList.toggle( 'is-checked' );
				li.setAttribute( 'aria-pressed', checked ? 'true' : 'false' );
			}

			li.addEventListener( 'click', toggle );
			li.addEventListener( 'keydown', function( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					toggle();
				}
			});
		});
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
