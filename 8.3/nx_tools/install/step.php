<?
/** @noinspection PhpUndefinedConstantInspection */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

global $step;

if ($step == 2):?>
    <?
    if (!check_bitrix_sessid()) return; ?>

    <?
    if ($ex = $APPLICATION->GetException()):?>
        <?= CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
                'DETAILS' => $ex->GetString(),
                'HTML' => true,
        ]) ?>
    <?
    else:?>
        <?= CAdminMessage::ShowNote(Loc::getMessage('MOD_INST_OK')); ?>
    <?
    endif; ?>

    <form action="<?= $APPLICATION->GetCurPage() ?>">
        <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>"/>
        <input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>"/>
    </form>
    <?
    return; ?>

<?else:?>
    <form action="<?= $APPLICATION->GetCurPage() ?>" name="form1">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>"/>
        <input type="hidden" name="id" value="nx_tools"/>
        <input type="hidden" name="install" value="Y"/>
        <input type="hidden" name="step" value="2"/>

        <input type="submit" name="inst" value="<?= Loc::getMessage('MOD_INSTALL') ?>"/>
    </form>
<?endif; ?>