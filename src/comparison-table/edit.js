/**
 * 比較テーブル（親） — エディタ表示
 * 1列目の見出し・各列名はエディタ内で編集。行は comparison-row 子ブロックとして追加する。
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Button, ToggleControl, Dropdown, ColorPalette, SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

const ALLOWED_BLOCKS = [ 'madoguchi/comparison-row' ];
const TEMPLATE = [ [ 'madoguchi/comparison-row' ] ];

export default function Edit( { attributes, setAttributes, clientId }) {
	const { caption, articleId, columns, accentColor, fontSize, nameLabel, nameColWidth, nameColBgColor, showCta, ctaLabel, ctaNoteFontSize, ctaNoteColor, headerBgColor, headerTextColor, cellAlign } = attributes;
	const [ dragIndex, setDragIndex ] = useState( null );
	// ドラッグはハンドル（⠿）でmousedownした列だけ有効にする。
	// チップ全体を draggable にすると内部の RichText（contenteditable）が
	// mousedown を先取りしてしまい、ドラッグそのものが始まらないため。
	const [ armedIndex, setArmedIndex ] = useState( null );

	const blockProps = useBlockProps({
		className: 'comparison-table comparison-table--edit' + ( 'left' === cellAlign ? ' comparison-table--align-left' : '' ),
		style: {
			...( accentColor ? { '--md-brand': accentColor } : {} ),
			...( fontSize ? { '--md-table-size': fontSize + 'px' } : {} ),
			...( nameColBgColor ? { '--md-name-bg': nameColBgColor } : {} ),
			...( ctaNoteFontSize ? { '--md-cta-note-size': ctaNoteFontSize + 'px' } : {} ),
			...( ctaNoteColor ? { '--md-cta-note-color': ctaNoteColor } : {} ),
			...( headerBgColor ? { '--md-header-bg': headerBgColor } : {} ),
			...( headerTextColor ? { '--md-header-text': headerTextColor } : {} )
		}
	});
	const innerProps = useInnerBlocksProps(
		{ className: 'comparison-table__rows-edit' },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: false }
	);

	// 子行ブロックを取得（列削除時に values を揃えるため）
	const childRows = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const updateColumnLabel = ( index, value ) => {
		const next = columns.map( ( c, i ) => ( i === index ? { ...c, label: value } : c ) );
		setAttributes({ columns: next });
	};

	// 列単位の文字サイズ・色（ヘッダー・値セルの両方に適用）を更新する
	const updateColumnSetting = ( index, key, value ) => {
		const next = columns.map( ( c, i ) => ( i === index ? { ...c, [ key ]: value } : c ) );
		setAttributes({ columns: next });
	};

	const addColumn = () => {
		setAttributes({ columns: [ ...columns, { label: '' } ] });
	};

	// 列削除時は全行の values から同じ位置を取り除く
	const removeColumn = ( index ) => {
		childRows.forEach( ( row ) => {
			const values = Array.isArray( row.attributes.values ) ? row.attributes.values.slice() : [];
			values.splice( index, 1 );
			updateBlockAttributes( row.clientId, { values });
		});
		setAttributes({ columns: columns.filter( ( _, i ) => i !== index ) });
	};

	// 配列の要素を fromIndex → toIndex へ移動する
	const reorder = ( arr, fromIndex, toIndex ) => {
		const next = arr.slice();
		const [ moved ] = next.splice( fromIndex, 1 );
		next.splice( toIndex, 0, moved );
		return next;
	};

	// 列の並べ替え時は全行の values も同じ順序に揃える
	const moveColumn = ( fromIndex, toIndex ) => {
		if ( fromIndex === toIndex || fromIndex < 0 || toIndex < 0 ) {
			return;
		}
		childRows.forEach( ( row ) => {
			const values = Array.isArray( row.attributes.values ) ? row.attributes.values.slice() : [];
			while ( values.length < columns.length ) {
				values.push( '' );
			}
			updateBlockAttributes( row.clientId, { values: reorder( values, fromIndex, toIndex ) });
		});
		setAttributes({ columns: reorder( columns, fromIndex, toIndex ) });
	};

	const armColumn = ( index ) => () => setArmedIndex( index );
	const disarmColumn = () => setArmedIndex( null );

	const handleDragStart = ( index ) => ( event ) => {
		setDragIndex( index );
		event.stopPropagation();
		// Firefox は dataTransfer.setData() が呼ばれないとドラッグを開始しない。
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData( 'text/plain', String( index ) );
	};
	const handleDragOver = ( event ) => {
		event.preventDefault();
		event.stopPropagation();
		event.dataTransfer.dropEffect = 'move';
	};
	const handleDrop = ( index ) => ( event ) => {
		event.preventDefault();
		event.stopPropagation();
		if ( null !== dragIndex ) {
			moveColumn( dragIndex, index );
		}
		setDragIndex( null );
	};
	const handleDragEnd = ( event ) => {
		event.stopPropagation();
		setDragIndex( null );
		setArmedIndex( null );
	};

	// ハンドルで mousedown した後、チップ外でマウスを離した場合も確実に解除する
	useEffect( () => {
		if ( null === armedIndex ) {
			return;
		}
		const onMouseUp = () => setArmedIndex( null );
		window.addEventListener( 'mouseup', onMouseUp );
		return () => window.removeEventListener( 'mouseup', onMouseUp );
	}, [ armedIndex ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'テーブル設定', 'madoguchi-blocks' ) }>
					<RangeControl
						label={ __( '文字サイズ（px）', 'madoguchi-blocks' ) }
						value={ fontSize }
						onChange={ ( value ) => setAttributes({ fontSize: value }) }
						min={ 10 }
						max={ 22 }
					/>
					<TextControl
						label={ __( '計測用の記事ID（任意）', 'madoguchi-blocks' ) }
						value={ articleId }
						onChange={ ( value ) => setAttributes({ articleId: value }) }
						help={ __( '未入力の場合は現在の記事IDを使用します。', 'madoguchi-blocks' ) }
					/>
					<RangeControl
						label={ __( '名称列の幅（px）', 'madoguchi-blocks' ) }
						value={ nameColWidth }
						onChange={ ( value ) => setAttributes({ nameColWidth: value }) }
						min={ 100 }
						max={ 280 }
						step={ 10 }
					/>
					<ToggleControl
						label={ __( 'CTA列を表示', 'madoguchi-blocks' ) }
						checked={ showCta }
						onChange={ ( value ) => setAttributes({ showCta: value }) }
						help={ __( 'オフにすると各行のCTAボタン列を非表示にします。', 'madoguchi-blocks' ) }
					/>
					<ToggleControl
						label={ __( 'セル内の文字を左揃えにする', 'madoguchi-blocks' ) }
						checked={ 'left' === cellAlign }
						onChange={ ( value ) => setAttributes({ cellAlign: value ? 'left' : 'center' }) }
						help={ __( 'オン（既定）は左揃え。オフにすると中央揃え。CTA列は対象外です。', 'madoguchi-blocks' ) }
					/>
					{ showCta && (
						<RangeControl
							label={ __( 'CTA下の補足文の文字サイズ（既定・px）', 'madoguchi-blocks' ) }
							value={ ctaNoteFontSize || 0 }
							onChange={ ( value ) => setAttributes({ ctaNoteFontSize: value }) }
							min={ 0 }
							max={ 20 }
							help={ __( '0は既定サイズ。各行の設定で個別に上書きできます。', 'madoguchi-blocks' ) }
						/>
					) }
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [ {
						value: accentColor,
						onChange: ( color ) => setAttributes({ accentColor: color || '' }),
						label: __( 'アクセントカラー', 'madoguchi-blocks' )
					}, {
						value: nameColBgColor,
						onChange: ( color ) => setAttributes({ nameColBgColor: color || '' }),
						label: __( '名称列の背景色', 'madoguchi-blocks' )
					}, {
						value: headerBgColor,
						onChange: ( color ) => setAttributes({ headerBgColor: color || '' }),
						label: __( 'ヘッダーの背景色', 'madoguchi-blocks' )
					}, {
						value: headerTextColor,
						onChange: ( color ) => setAttributes({ headerTextColor: color || '' }),
						label: __( 'ヘッダーの文字色', 'madoguchi-blocks' )
					}, ...( showCta ? [ {
						value: ctaNoteColor,
						onChange: ( color ) => setAttributes({ ctaNoteColor: color || '' }),
						label: __( 'CTA下の補足文の文字色（既定）', 'madoguchi-blocks' )
					} ] : [] ) ] }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="p"
					className="comparison-table__caption"
					value={ caption }
					onChange={ ( value ) => setAttributes({ caption: value }) }
					placeholder={ __( '見出し（例：おすすめサービスを比較）', 'madoguchi-blocks' ) }
					allowedFormats={ [] }
				/>

				<div className="comparison-table__columns-edit">
					<span className="comparison-table__columns-edit-label">{ __( '列（ドラッグで並べ替え）：', 'madoguchi-blocks' ) }</span>
					<span className="comparison-table__col-chip comparison-table__col-chip--name">
						<RichText
							tagName="span"
							className="comparison-table__col-label"
							value={ nameLabel || '' }
							onChange={ ( value ) => setAttributes({ nameLabel: value }) }
							placeholder={ __( '1列目の見出し（例：業者・商品名）', 'madoguchi-blocks' ) }
							allowedFormats={ [] }
						/>
						<span className="comparison-table__col-fixed-mark">{ __( '（固定）', 'madoguchi-blocks' ) }</span>
					</span>
					{ columns.map( ( col, index ) => (
						<span
							className={ 'comparison-table__col-chip' + ( dragIndex === index ? ' is-dragging' : '' ) }
							key={ index }
							draggable={ armedIndex === index }
							onDragStart={ handleDragStart( index ) }
							onDragOver={ handleDragOver }
							onDrop={ handleDrop( index ) }
							onDragEnd={ handleDragEnd }
						>
							<span
								className="comparison-table__col-drag"
								aria-hidden="true"
								onMouseDown={ armColumn( index ) }
								onMouseUp={ disarmColumn }
							>⠿</span>
							<button
								type="button"
								className="comparison-table__col-move"
								onClick={ () => moveColumn( index, index - 1 ) }
								disabled={ 0 === index }
								aria-label={ __( '左へ移動', 'madoguchi-blocks' ) }
							>←</button>
							<button
								type="button"
								className="comparison-table__col-move"
								onClick={ () => moveColumn( index, index + 1 ) }
								disabled={ index === columns.length - 1 }
								aria-label={ __( '右へ移動', 'madoguchi-blocks' ) }
							>→</button>
							{ col.group && (
								<span
									className="comparison-table__col-group-tag"
									style={ { background: col.groupColor || '#f3e3a0' } }
									title={ __( 'グループ見出し（2段ヘッダーの上段）', 'madoguchi-blocks' ) }
								>{ col.group }</span>
							) }
							<RichText
								tagName="span"
								className="comparison-table__col-label"
								style={ {
									...( col.fontSize ? { fontSize: col.fontSize + 'px' } : {} ),
									...( col.color ? { color: col.color } : {} )
								} }
								value={ col.label || '' }
								onChange={ ( value ) => updateColumnLabel( index, value ) }
								placeholder={ __( '列名', 'madoguchi-blocks' ) }
								allowedFormats={ [] }
							/>
							<Dropdown
								className="comparison-table__col-settings"
								contentClassName="comparison-table__col-settings-popover"
								renderToggle={ ( { isOpen, onToggle } ) => (
									<button
										type="button"
										className="comparison-table__col-gear"
										onClick={ onToggle }
										aria-expanded={ isOpen }
										aria-label={ __( 'この列の文字サイズ・色', 'madoguchi-blocks' ) }
									>⚙</button>
								) }
								renderContent={ () => (
									<div className="comparison-table__col-settings-panel">
										<SelectControl
											label={ __( '列の種別', 'madoguchi-blocks' ) }
											value={ col.type || 'text' }
											options={ [
												{ label: __( 'テキスト', 'madoguchi-blocks' ), value: 'text' },
												{ label: __( '✓／✕（チェック）', 'madoguchi-blocks' ), value: 'check' },
												{ label: __( '口コミ（☆評価／PR）', 'madoguchi-blocks' ), value: 'review' },
												{ label: __( '検証スコア（☆）', 'madoguchi-blocks' ), value: 'score' }
											] }
											onChange={ ( value ) => updateColumnSetting( index, 'type', value ) }
											help={ __( '種別を変えると、各行のこの列の入力方法が切り替わります。', 'madoguchi-blocks' ) }
										/>
										<TextControl
											label={ __( 'グループ見出し（2段ヘッダー）', 'madoguchi-blocks' ) }
											value={ col.group || '' }
											onChange={ ( value ) => updateColumnSetting( index, 'group', value ) }
											placeholder={ __( '例：対応可能な買取方法', 'madoguchi-blocks' ) }
											help={ __( '隣り合う列に同じ見出しを設定すると、まとめて上段の見出しになります。空欄なら1段のまま。', 'madoguchi-blocks' ) }
										/>
										{ col.group && (
											<>
												<p className="comparison-table__col-settings-label">{ __( 'グループ見出しの背景色（既定: #f3e3a0・グループ先頭列の設定を使用）', 'madoguchi-blocks' ) }</p>
												<ColorPalette
													value={ col.groupColor }
													onChange={ ( value ) => updateColumnSetting( index, 'groupColor', value || '' ) }
													enableAlpha={ false }
													clearable
												/>
											</>
										) }
										<RangeControl
											label={ __( '列の横幅（px・0で自動）', 'madoguchi-blocks' ) }
											value={ col.width || 0 }
											onChange={ ( value ) => updateColumnSetting( index, 'width', value ) }
											min={ 0 }
											max={ 400 }
											step={ 10 }
										/>
										<RangeControl
											label={ __( '文字サイズ（px・0で既定値）', 'madoguchi-blocks' ) }
											value={ col.fontSize || 0 }
											onChange={ ( value ) => updateColumnSetting( index, 'fontSize', value ) }
											min={ 0 }
											max={ 28 }
										/>
										<p className="comparison-table__col-settings-label">{ __( '文字色', 'madoguchi-blocks' ) }</p>
										<ColorPalette
											value={ col.color }
											onChange={ ( value ) => updateColumnSetting( index, 'color', value || '' ) }
											enableAlpha={ false }
											clearable
										/>
									</div>
								) }
							/>
							<button
								type="button"
								className="comparison-table__col-remove"
								onClick={ () => removeColumn( index ) }
								aria-label={ __( 'この列を削除', 'madoguchi-blocks' ) }
							>×</button>
						</span>
					) ) }
					{ showCta && (
						<span className="comparison-table__col-chip comparison-table__col-chip--cta">
							<RichText
								tagName="span"
								className="comparison-table__col-label"
								value={ ctaLabel || '' }
								onChange={ ( value ) => setAttributes({ ctaLabel: value }) }
								placeholder={ __( 'CTA見出し（例：詳細へ）', 'madoguchi-blocks' ) }
								allowedFormats={ [] }
							/>
							<span className="comparison-table__col-fixed-mark">{ __( '（固定）', 'madoguchi-blocks' ) }</span>
						</span>
					) }
					<Button variant="secondary" size="small" icon="plus" onClick={ addColumn }>
						{ __( '列を追加', 'madoguchi-blocks' ) }
					</Button>
				</div>

				<div { ...innerProps } />
			</div>
		</>
	);
}
