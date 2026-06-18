<?
/** @noinspection PhpUndefinedConstantInspection */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

/** @global CDatabase $DB */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $step;

if ($step == 2):?>
    <?if (!check_bitrix_sessid()) return;?>
    <?if ($ex = $APPLICATION->GetException()):?>
        <?= CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage('MOD_UNINST_ERR'),
                'DETAILS' => $ex->GetString(),
                'HTML' => true,
        ])?>
    <?else:?>
        <?=CAdminMessage::ShowNote(GetMessage('MOD_UNINST_OK'))?>
    <?endif?>

    <form action="<?= $APPLICATION->GetCurPage() ?>">
        <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>"/>
        <input type="submit" name="" value="<?= GetMessage('MOD_BACK') ?>"/>
    </form>
    <?return;?>
<?endif;?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>"/>
    <input type="hidden" name="id" value="nx_tools"/>
    <input type="hidden" name="uninstall" value="Y"/>
    <input type="hidden" name="step" value="2"/>

    <?= CAdminMessage::ShowMessage(Loc::getMessage('MOD_UNINST_WARN')) ?>

    <p><?= Loc::getMessage ('MOD_UNINST_SAVE') ?></p>
    <p>
        <input type="checkbox" name="savedata" id="savedata" value="Y" checked="checked"/>
        <label for="savedata"><?= Loc::getMessage('MOD_UNINST_SAVE_TABLES') ?></label>
    </p>
    <p>
        <input type="checkbox" name="save_templates" id="save_templates" value="Y" checked="checked"/>
        <label for="save_templates"><?= Loc::getMessage('MOD_UNINST_SAVE_EVENTS') ?></label>
    </p>
    <input type="submit" name="inst" value="<?= Loc::getMessage('MOD_UNINST_DEL') ?>"/>
</form>