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

namespace ILIAS\Badge;

use ILIAS\UI\Factory;
use ILIAS\UI\URLBuilder;
use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ilLanguage;
use ilGlobalTemplateInterface;
use ILIAS\UI\Renderer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use ILIAS\UI\Component\Table\DataRowBuilder;
use Generator;
use ILIAS\UI\Component\Table\DataRetrieval;
use ilBadgeHandler;
use ilObject;
use ilBadge;
use ilBadgeAssignment;
use ilUserQuery;
use DateTimeImmutable;
use ILIAS\UI\URLBuilderToken;
use ilObjectDataDeletionLog;
use ilTree;
use ilCalendarSettings;
use ilObjUser;

class ilBadgeUserTableGUI
{
    private readonly Factory $factory;
    private readonly Renderer $renderer;
    private readonly ServerRequestInterface|RequestInterface $request;
    private readonly int $parent_ref_id;
    private readonly ilLanguage $lng;
    private readonly ilGlobalTemplateInterface $tpl;
    private readonly ilTree $tree;
    private readonly ilObjUser $user;

    public function __construct(
        int $parent_ref_id,
        private readonly ?ilBadge $award_badge = null,
        private readonly ?int $restrict_badge_id = null
    ) {
        global $DIC;

        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->request = $DIC->http()->request();
        $this->tree = $DIC->repositoryTree();
        $this->user = $DIC->user();
        $this->parent_ref_id = $parent_ref_id;
    }

    private function buildDataRetrievalObject(
        Factory $f,
        Renderer $r,
        ilTree $tree,
        ilObjUser $user,
        int $parent_ref_id,
        ?int $restrict_badge_id = null,
        ?ilBadge $award_badge = null
    ): DataRetrieval {
        return new class ($f, $r, $tree, $user, $parent_ref_id, $restrict_badge_id, $award_badge) implements DataRetrieval {
            public function __construct(
                private readonly Factory $ui_factory,
                private readonly Renderer $ui_renderer,
                private readonly ilTree $tree,
                private readonly ilObjUser $user,
                private readonly int $parent_ref_id,
                private readonly ?int $restrict_badge_id = null,
                private readonly ?ilBadge $award_badge = null
            ) {
            }

            /**
             * @return list<array{
             *     id: string,
             *     name: string,
             *     login: string,
             *     type: string,
             *     title: string,
             *     issued: ?DateTimeImmutable
             *  }>
             */
            private function getBadgeImageTemplates(): array
            {
                /** @var array<int, list<ilBadgeAssignment>> $assignments */
                $assignments = [];
                $user_ids = [];
                $rows = [];
                $badges = [];

                $a_parent_obj_id = ilObject::_lookupObjId($this->parent_ref_id);
                if ($this->parent_ref_id) {
                    $user_ids = ilBadgeHandler::getInstance()->getUserIds($this->parent_ref_id, $a_parent_obj_id);
                }

                $obj_ids = [$a_parent_obj_id];
                foreach ($this->tree->getSubTree($this->tree->getNodeData($this->parent_ref_id)) as $node) {
                    $obj_ids[] = (int) $node['obj_id'];
                }

                $obj_ids = array_unique($obj_ids);
                foreach ($obj_ids as $obj_id) {
                    foreach (ilBadge::getInstancesByParentId($obj_id) as $badge) {
                        $badges[$badge->getId()] = $badge;
                    }

                    foreach (ilBadgeAssignment::getInstancesByParentId($obj_id) as $ass) {
                        if ($this->restrict_badge_id && $this->restrict_badge_id !== $ass->getBadgeId()) {
                            continue;
                        }

                        if ($this->award_badge && $ass->getBadgeId() !== $this->award_badge->getId()) {
                            continue;
                        }

                        $assignments[$ass->getUserId()][] = $ass;
                    }
                }

                if (!$user_ids) {
                    $user_ids = array_keys($assignments);
                }

                $tmp['set'] = [];
                if (\count($user_ids) > 0) {
                    $uquery = new ilUserQuery();
                    $uquery->setLimit(9999);
                    $uquery->setUserFilter($user_ids);
                    $tmp = $uquery->query();
                }

                foreach ($tmp['set'] as $user) {
                    if (\array_key_exists($user['usr_id'], $assignments)) {
                        foreach ($assignments[$user['usr_id']] as $user_ass) {
                            $idx = $user_ass->getBadgeId() . '-' . $user['usr_id'];

                            $badge = $badges[$user_ass->getBadgeId()];

                            $rows[] = [
                                'id' => $idx,
                                'name' => $user['lastname'] . ', ' . $user['firstname'],
                                'login' => $user['login'],
                                'type' => ilBadge::getExtendedTypeCaption($badge->getTypeInstance()),
                                'title' => $badge->getTitle(),
                                'issued' => (new DateTimeImmutable())
                                    ->setTimestamp($user_ass->getTimestamp())
                                    ->setTimezone(new \DateTimeZone($this->user->getTimeZone()))
                            ];
                        }
                    } elseif ($this->award_badge) {
                        $idx = '0-' . $user['usr_id'];

                        $rows[] = [
                            'id' => $idx,
                            'name' => $user['lastname'] . ', ' . $user['firstname'],
                            'login' => $user['login'],
                            'type' => '',
                            'title' => '',
                            'issued' => null,
                        ];
                    }
                }

                return $rows;
            }

            public function getRows(
                DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ): Generator {
                $records = $this->getRecords($range, $order);
                foreach ($records as $record) {
                    yield $row_builder->buildDataRow($record['id'], $record);
                }
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ): ?int {
                return \count($this->getRecords());
            }

            /**
             * @return list<array{
             *     id: string,
             *     name: string,
             *     login: string,
             *     type: string,
             *     title: string,
             *     issued: ?DateTimeImmutable
             *  }>
             */
            private function getRecords(Range $range = null, Order $order = null): array
            {
                $rows = $this->getBadgeImageTemplates();

                if ($order) {
                    [$order_field, $order_direction] = $order->join(
                        [],
                        fn($ret, $key, $value) => [$key, $value]
                    );
                    usort(
                        $rows,
                        static function (array $left, array $right) use ($order_field): int {
                            if (\in_array($order_field, ['name', 'login', 'type', 'title'], true)) {
                                return \ilStr::strCmp(
                                    $left[$order_field],
                                    $right[$order_field ]
                                );
                            }

                            return $left[$order_field] <=> $right[$order_field];
                        }
                    );
                    if ($order_direction === ORDER::DESC) {
                        $rows = array_reverse($rows);
                    }
                }

                if ($range) {
                    $rows = \array_slice($rows, $range->getStart(), $range->getLength());
                }

                return $rows;
            }
        };
    }

    /**
     * @return array<string, \ILIAS\UI\Component\Table\Action\Action>
     */
    private function getActions(
        URLBuilder $url_builder,
        URLBuilderToken $action_parameter_token,
        URLBuilderToken $row_id_token,
    ): array {
        if ($this->award_badge) {
            $f = $this->factory;

            return [
                'badge_award_badge' =>
                    $f->table()->action()->multi(
                        $this->lng->txt('badge_award_badge'),
                        $url_builder->withParameter($action_parameter_token, 'assignBadge'),
                        $row_id_token
                    ),
                'badge_revoke_badge' =>
                    $f->table()->action()->multi(
                        $this->lng->txt('badge_remove_badge'),
                        $url_builder->withParameter($action_parameter_token, 'revokeBadge'),
                        $row_id_token
                    ),
            ];
        }

        return [];
    }

    public function renderTable(): void
    {
        $f = $this->factory;
        $r = $this->renderer;
        $request = $this->request;

        $df = new \ILIAS\Data\Factory();
        if ((int) $this->user->getTimeFormat() === ilCalendarSettings::TIME_FORMAT_12) {
            $date_format = $df->dateFormat()->withTime12($this->user->getDateFormat());
        } else {
            $date_format = $df->dateFormat()->withTime24($this->user->getDateFormat());
        }

        $columns = [
            'name' => $f->table()->column()->text($this->lng->txt('name')),
            'login' => $f->table()->column()->text($this->lng->txt('login')),
            'type' => $f->table()->column()->text($this->lng->txt('type')),
            'title' => $f->table()->column()->text($this->lng->txt('title')),
            'issued' => $f->table()->column()->date(
                $this->lng->txt('badge_issued_on'),
                $date_format
            )
        ];

        $table_uri = $df->uri($request->getUri()->__toString());
        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ['tid'];

        [$url_builder, $action_parameter_token, $row_id_token] =
            $url_builder->acquireParameters(
                $query_params_namespace,
                'table_action',
                'id',
            );

        $data_retrieval = $this->buildDataRetrievalObject(
            $f,
            $r,
            $this->tree,
            $this->user,
            $this->parent_ref_id,
            $this->restrict_badge_id,
            $this->award_badge
        );
        $actions = $this->getActions($url_builder, $action_parameter_token, $row_id_token);

        if ($this->award_badge) {
            $title = $this->lng->txt('badge_award_badge') . ': ' . $this->award_badge->getTitle();
        } else {
            $parent = '';
            $parent_obj_id = ilObject::_lookupObjId($this->parent_ref_id);
            if ($parent_obj_id) {
                $title = ilObject::_lookupTitle($parent_obj_id);
                if (!$title) {
                    $title = ilObjectDataDeletionLog::get($parent_obj_id);
                    if ($title) {
                        $title = $title['title'];
                    }
                }

                if ($this->restrict_badge_id) {
                    $badge = new ilBadge($this->restrict_badge_id);
                    $title .= ' - ' . $badge->getTitle();
                }

                $parent = $title . ': ';
            }
            $title = $parent . $this->lng->txt('users');
        }

        $table = $f->table()
                   ->data($title, $columns, $data_retrieval)
                   ->withId(self::class . '_' . $this->parent_ref_id)
                   ->withOrder(new Order('name', Order::ASC))
                   ->withActions($actions)
                   ->withRequest($request);

        $out = [$table];

        $this->tpl->setContent($r->render($out));
    }
}
