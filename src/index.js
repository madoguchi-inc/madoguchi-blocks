/**
 * madoguchi-blocks — エディタ用エントリ
 * チェックリスト型CTA / 条件別カードリンク（親＋子）を登録する。
 */

import { registerBlockType } from '@wordpress/blocks';

import checklistMeta from '../blocks/checklist-cta/block.json';
import checklistEdit from './checklist-cta/edit';
import checklistSave from './checklist-cta/save';
import checklistDeprecated from './checklist-cta/deprecated';

import cardMeta from '../blocks/condition-card/block.json';
import cardEdit from './condition-card/edit';
import cardSave from './condition-card/save';
import cardDeprecated from './condition-card/deprecated';

import cardsMeta from '../blocks/condition-cards/block.json';
import cardsEdit from './condition-cards/edit';
import cardsSave from './condition-cards/save';

// 動的ブロック（save は null、描画は render.php）
import authorBoxMeta from '../blocks/author-box/block.json';
import authorBoxEdit from './author-box/edit';
import authorBoxSave from './author-box/save';

import reviewMeta from '../blocks/review-section/block.json';
import reviewEdit from './review-section/edit';
import reviewSave from './review-section/save';

import comparisonMeta from '../blocks/comparison-table/block.json';
import comparisonEdit from './comparison-table/edit';
import comparisonSave from './comparison-table/save';

import comparisonRowMeta from '../blocks/comparison-row/block.json';
import comparisonRowEdit from './comparison-row/edit';
import comparisonRowSave from './comparison-row/save';

import ctaMeta from '../blocks/cta-button/block.json';
import ctaEdit from './cta-button/edit';
import ctaSave from './cta-button/save';

import recommendMeta from '../blocks/recommend-card/block.json';
import recommendEdit from './recommend-card/edit';
import recommendSave from './recommend-card/save';

// 子（condition-card）を先に登録してから親を登録する
// （checklist / condition-card は旧マークアップからの自動移行用に deprecated を持つ）
registerBlockType( checklistMeta, { edit: checklistEdit, save: checklistSave, deprecated: checklistDeprecated });
registerBlockType( cardMeta, { edit: cardEdit, save: cardSave, deprecated: cardDeprecated });
registerBlockType( cardsMeta, { edit: cardsEdit, save: cardsSave });

// 動的ブロック
registerBlockType( authorBoxMeta, { edit: authorBoxEdit, save: authorBoxSave });
registerBlockType( reviewMeta, { edit: reviewEdit, save: reviewSave });
registerBlockType( comparisonRowMeta, { edit: comparisonRowEdit, save: comparisonRowSave });
registerBlockType( comparisonMeta, { edit: comparisonEdit, save: comparisonSave });
registerBlockType( ctaMeta, { edit: ctaEdit, save: ctaSave });
registerBlockType( recommendMeta, { edit: recommendEdit, save: recommendSave });
