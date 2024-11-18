<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Repository\Clipboard\ClipboardManager;
use ILIAS\Container\Content\ViewManager;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Refinery\Factory;
use ILIAS\Object\ImplementsCreationCallback;
use ILIAS\Object\CreationCallbackTrait;

/**
* Class ilSearchBaseGUI
*
* Base class for all search gui classes. Offers functionallities like set Locator set Header ...
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
*
* @package ilias-search
*
* @ilCtrl_IsCalledBy ilSearchBaseGUI: ilSearchControllerGUI
*
*
*/
class ilSearchBaseGUI implements ilDesktopItemHandling, ilAdministrationCommandHandling, ImplementsCreationCallback
{
    use CreationCallbackTrait;

    public const SEARCH_FAST = 1;
    public const SEARCH_DETAILS = 2;
    public const SEARCH_AND = 'and';
    public const SEARCH_OR = 'or';

    public const SEARCH_FORM_LUCENE = 1;
    public const SEARCH_FORM_STANDARD = 2;
    public const SEARCH_FORM_USER = 3;

    protected ilUserSearchCache $search_cache;
    protected string $search_mode = '';

    protected ilSearchSettings $settings;
    protected ?ilPropertyFormGUI $form = null;
    protected ?ilSearchFilterGUI $search_filter = null;
    protected ?array $search_filter_data = null;
    protected ClipboardManager $clipboard;
    protected ViewManager $container_view_manager;
    protected ilFavouritesManager $favourites;

    protected ilCtrl $ctrl;
    protected ILIAS $ilias;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    protected ilLocatorGUI $locator;
    protected ilObjUser $user;
    protected ilTree $tree;
    protected GlobalHttpState $http;
    protected Factory $refinery;

    protected ilLogger $logger;


    protected string $prev_link = '';
    protected string $next_link = '';

    public function __construct()
    {
        global $DIC;


        $this->logger = $DIC->logger()->src();
        $this->ilias = $DIC['ilias'];
        $this->locator = $DIC['ilLocator'];
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tree = $DIC->repositoryTree();

        $this->lng->loadLanguageModule('search');
        $this->settings = new ilSearchSettings();
        $this->favourites = new ilFavouritesManager();
        $this->user = $DIC->user();
        $this->clipboard = $DIC
            ->repository()
            ->internal()
            ->domain()
            ->clipboard();
        $this->search_cache = ilUserSearchCache::_getInstance($this->user->getId());
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
    }

    protected function initPageNumberFromQuery(): int
    {
        if ($this->http->wrapper()->query()->has('page_number')) {
            return $this->http->wrapper()->query()->retrieve(
                'page_number',
                $this->refinery->kindlyTo()->int()
            );
        }
        return 0;
    }


    public function prepareOutput(): void
    {
        $this->tpl->loadStandardTemplate();

        $this->tpl->setTitleIcon(
            ilObject::_getIcon(0, "big", "src"),
            ""
        );
        $this->tpl->setTitle($this->lng->txt("search"));
    }

    /**
     * @todo: Check if this can be removed. Still used in ilLuceneUserSearchGUI?
     */
    public function initStandardSearchForm(int $a_mode): ilPropertyFormGUI
    {
        $this->form = new ilPropertyFormGUI();
        $this->form->setOpenTag(false);
        $this->form->setCloseTag(false);

        if (ilSearchSettings::getInstance()->isLuceneItemFilterEnabled()) {
            $radg = new ilRadioGroupInputGUI($this->lng->txt("search_type"), "type");
            if ($a_mode == self::SEARCH_FORM_STANDARD) {
                // search type
                $radg->setValue(
                    $this->getType() ==
                        self::SEARCH_FAST ?
                        (string) self::SEARCH_FAST :
                        (string) self::SEARCH_DETAILS
                );
                $op1 = new ilRadioOption($this->lng->txt("search_fast_info"), (string) self::SEARCH_FAST);
                $radg->addOption($op1);
                $op2 = new ilRadioOption($this->lng->txt("search_details_info"), (string) self::SEARCH_DETAILS);
            } else {
                $op2 = new ilCheckboxInputGUI($this->lng->txt('search_filter_by_type'), 'item_filter_enabled');
                $op2->setValue('1');
            }


            $cbgr = new ilCheckboxGroupInputGUI('', 'filter_type');
            $cbgr->setUseValuesAsKeys(true);
            $details = $this->getDetails();
            $det = false;
            foreach (ilSearchSettings::getInstance()->getEnabledLuceneItemFilterDefinitions() as $type => $data) {
                $cb = new ilCheckboxOption($this->lng->txt($data['trans']), $type);
                if (isset($details[$type])) {
                    $det = true;
                }
                $cbgr->addOption($cb);
            }
            $mimes = [];
            if ($a_mode == self::SEARCH_FORM_LUCENE) {
                if (ilSearchSettings::getInstance()->isLuceneMimeFilterEnabled()) {
                    $mimes = $this->getMimeDetails();
                    foreach (ilSearchSettings::getInstance()->getEnabledLuceneMimeFilterDefinitions() as $type => $data) {
                        $op3 = new ilCheckboxOption($this->lng->txt($data['trans']), $type);
                        if (isset($mimes[$type])) {
                            $det = true;
                        }
                        $cbgr->addOption($op3);
                    }
                }
            }

            $cbgr->setValue(array_merge((array) $details, (array) $mimes));
            $op2->addSubItem($cbgr);

            if ($a_mode != self::SEARCH_FORM_STANDARD && $det) {
                $op2->setChecked(true);
            }

            if ($a_mode == self::SEARCH_FORM_STANDARD) {
                $radg->addOption($op2);
                $this->form->addItem($radg);
            } else {
                $this->form->addItem($op2);
            }
        }

        $this->form->setFormAction($this->ctrl->getFormAction($this, 'performSearch'));
        return $this->form;
    }

    public function handleCommand(string $a_cmd): void
    {
        if (method_exists($this, $a_cmd)) {
            $this->$a_cmd();
        } else {
            $a_cmd .= 'Object';
            $this->$a_cmd();
        }
    }

    public function addToDeskObject(): void
    {
        if ($this->http->wrapper()->query()->has('item_ref_id')) {
            $this->favourites->add(
                $this->user->getId(),
                $this->http->wrapper()->query()->retrieve(
                    'item_ref_id',
                    $this->refinery->kindlyTo()->int()
                )
            );
        }
        $this->showSavedResults();
    }

    public function removeFromDeskObject(): void
    {
        if ($this->http->wrapper()->query()->has('item_ref_id')) {
            $this->favourites->remove(
                $this->user->getId(),
                $this->http->wrapper()->query()->retrieve(
                    'item_ref_id',
                    $this->refinery->kindlyTo()->int()
                )
            );
        }
        $this->showSavedResults();
    }

    public function delete(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->delete();
    }

    public function cancelDelete(): void
    {
        $this->showSavedResults();
    }

    public function cancelObject(): void
    {
        $this->showSavedResults();
    }

    public function cancelMoveLinkObject(): void
    {
        $this->showSavedResults();
    }

    public function performDelete(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->performDelete();
    }

    public function cut(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->cut();
    }


    public function link(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->link();
    }

    public function paste(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->paste();
    }

    public function showLinkIntoMultipleObjectsTree(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->showLinkIntoMultipleObjectsTree();
    }

    public function showPasteTree(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->showPasteTree();
    }


    public function showMoveIntoObjectTree(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->showMoveIntoObjectTree();
    }

    public function performPasteIntoMultipleObjects(): void
    {
        $admin = new ilAdministrationCommandGUI($this);
        $admin->performPasteIntoMultipleObjects();
    }

    public function clear(): void
    {
        $this->clipboard->clear();
        $this->ctrl->redirect($this);
    }

    public function enableAdministrationPanel(): void
    {
    }

    public function disableAdministrationPanel(): void
    {
    }

    /**
     * @inheritdoc
     */
    public function keepObjectsInClipboardObject(): void
    {
        $this->ctrl->redirect($this);
    }


    public function addLocator(): void
    {
        $this->locator->addItem($this->lng->txt('search'), $this->ctrl->getLinkTarget($this));
        $this->tpl->setLocator();
    }

    /**
     * @todo check wether result is ilSearchResult or ilLuceneSearchResult and add interface or base class.
     */
    protected function addPager($result, string $a_session_key): bool
    {
        $max_page = max(ilSession::get($a_session_key), $this->search_cache->getResultPageNumber());
        ilSession::set($a_session_key, $max_page);

        if ($max_page == 1 and
            (count($result->getResults()) < $result->getMaxHits())) {
            return true;
        }

        if ($this->search_cache->getResultPageNumber() > 1) {
            $this->ctrl->setParameter($this, 'page_number', $this->search_cache->getResultPageNumber() - 1);
            $this->prev_link = $this->ctrl->getLinkTarget($this, 'performSearch');
        }
        for ($i = 1;$i <= $max_page;$i++) {
            if ($i == $this->search_cache->getResultPageNumber()) {
                continue;
            }

            $this->ctrl->setParameter($this, 'page_number', $i);
            $link = '<a href="' . $this->ctrl->getLinkTarget($this, 'performSearch') . '" /a>' . $i . '</a> ';
        }
        if (count($result->getResults()) >= $result->getMaxHits()) {
            $this->ctrl->setParameter($this, 'page_number', $this->search_cache->getResultPageNumber() + 1);
            $this->next_link = $this->ctrl->getLinkTarget($this, 'performSearch');
        }
        $this->ctrl->clearParameters($this);
        return false;
    }

    protected function buildSearchAreaPath(int $a_root_node): string
    {
        $path_arr = $this->tree->getPathFull($a_root_node, ROOT_FOLDER_ID);
        $counter = 0;
        $path = '';
        foreach ($path_arr as $data) {
            if ($counter++) {
                $path .= " > ";
                $path .= $data['title'];
            } else {
                $path .= $this->lng->txt('repository');
            }
        }
        return $path;
    }

    public function autoComplete(): void
    {
        $query = '';
        if ($this->http->wrapper()->post()->has('term')) {
            $query = $this->http->wrapper()->post()->retrieve(
                'term',
                $this->refinery->kindlyTo()->string()
            );
        }
        $list = ilSearchAutoComplete::getList($query);
        echo $list;
        exit;
    }

    protected function getSearchCache(): ilUserSearchCache
    {
        return $this->search_cache;
    }

    /**
     * @return array<{date_start: string, date_end: string}>
     */
    protected function loadCreationFilter(): array
    {
        if (!$this->settings->isDateFilterEnabled()) {
            return [];
        }

        $options = [];
        if (isset($this->search_filter_data["search_date"])) {
            $options["date_start"] = $this->search_filter_data["search_date"][0];
            $options["date_end"] = $this->search_filter_data["search_date"][1];
        }

        return $options;
    }

    protected function renderSearch(string $term, int $root_node = 0)
    {
        $this->tpl->addJavascript("assets/js/Search.js");

        $this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "performSearch"));
        $this->tpl->setVariable("TERM", ilLegacyFormElementsUtil::prepareFormOutput($term));
        $this->tpl->setVariable("SEARCH_LABEL", $this->lng->txt("search"));
        $btn = ilSubmitButton::getInstance();
        $btn->setCommand("performSearch");
        $btn->setCaption("search");
        $this->tpl->setVariable("SUBMIT_BTN", $btn->render());

        if ($root_node) {
            $this->renderFilter($root_node);
        }
    }

    protected function renderFilter(int $root_node)
    {
        $filter_html = $this->search_filter->getHTML();
        preg_match('/id="([^"]+)"/', $filter_html, $matches);
        $filter_id = $matches[1];
        $this->tpl->setVariable("SEARCH_FILTER", $filter_html);
        // scope in filter must be manipulated by JS if search is triggered in meta bar
        $this->tpl->addOnLoadCode("il.Search.syncFilterScope('" . $filter_id . "', '" . $root_node . "');");
    }

    protected function initFilter(int $mode)
    {
        $this->search_filter = new ilSearchFilterGUI($this, $mode);
        $this->search_filter_data = $this->search_filter->getData();
    }

    protected function getStringArrayTransformation(): ILIAS\Refinery\Transformation
    {
        return $this->refinery->custom()->transformation(
            static function (array $arr): array {
                // keep keys(!), transform all values to string
                return array_map(
                    static function ($v): string {
                        return \ilUtil::stripSlashes((string) $v);
                    },
                    $arr
                );
            }
        );
    }
}
