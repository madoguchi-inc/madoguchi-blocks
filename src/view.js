/**
 * madoguchi-blocks — フロント用スクリプト
 * - チェックリスト型CTA: チェック項目をクリック/タップすると ✓ をトグルする
 * - 比較テーブル: 横スクロール発生時だけヒントを表示する
 * - 条件カード: カード全体のクリックを内側のCTAボタンへ委譲する
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

	// 条件カード（新マークアップ）: カード全体のクリックを内側のCTAボタンへ委譲する
	// （旧マークアップはカード自体が <a> のため div のカードだけが対象）
	function initCardLinks() {
		var cards = document.querySelectorAll( 'div.condition-card' );
		cards.forEach( function( card ) {
			var link = card.querySelector( 'a.cta-button' );
			if ( ! link ) {
				return;
			}
			card.classList.add( 'has-link' );
			card.addEventListener( 'click', function( e ) {
				// ボタン自身（または他のリンク）のクリックは二重遷移を避けてそのまま通す
				if ( e.target.closest( 'a' ) ) {
					return;
				}
				link.click();
			});
		});
	}

	function init() {
		initScrollHints();
		initCardLinks();
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
