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

namespace ILIAS\Export\ImportHandler\Schema\Folder;

use DirectoryIterator;
use ILIAS\Data\Version;
use ILIAS\Export\ImportHandler\I\FactoryInterface as ImportHandlerFactoryInterface;
use ILIAS\Export\ImportHandler\I\Schema\Folder\HandlerInterface as SchemaFolderInterface;
use ILIAS\Export\ImportHandler\I\Schema\Info\CollectionInterface as SchemaInfoCollectionInterface;
use ILIAS\Export\ImportHandler\I\Schema\Info\HandlerInterface as SchemaInfoInterface;
use ilLogger;
use SplFileInfo;

class Handler implements SchemaFolderInterface
{
    protected const FILE_EXTENSION = 'xsd';
    protected const FILE_PREFIX = 'ilias_';
    protected const SCHEMA_DEFINITION_LOCATION = '../components/ILIAS/Export/xml/SchemaValidation';
    protected ImportHandlerFactoryInterface $import_handler;
    protected SchemaInfoCollectionInterface $collection;
    protected ilLogger $logger;

    public function __construct(
        ImportHandlerFactoryInterface $import_handler,
        ilLogger $logger
    ) {
        $this->import_handler = $import_handler;
        $this->logger = $logger;
        $this->collection = $import_handler->schema()->info()->collection();
        $this->readSchemaFiles();
    }

    public function getLatest(string $type, string $sub_type = ''): SchemaInfoInterface|null
    {
        return $this->collection->getLatest($type, $sub_type);
    }

    public function getByVersion(Version $version, string $type, string $sub_type = ''): SchemaInfoInterface|null
    {
        return $this->collection->getByVersion($version, $type, $sub_type);
    }

    public function getByVersionOrLatest(Version $version, string $type, string $sub_type = ''): SchemaInfoInterface|null
    {
        return $this->collection->getByVersionOrLatest($version, $type, $sub_type);
    }

    private function readSchemaFiles(): void
    {
        foreach (new DirectoryIterator(self::SCHEMA_DEFINITION_LOCATION) as $file) {
            if (
                $file->isDot() ||
                $file->getExtension() !== self::FILE_EXTENSION ||
                !str_starts_with($file->getFilename(), self::FILE_PREFIX)
            ) {
                continue;
            }
            $matches = [];
            if (preg_match('/ilias_([a-zA-Z]+)(_([a-zA-Z]+))?_([3-9]|([1-9][0-9]+))_?([0-9]+)?.xsd/', $file->getFilename(), $matches) !== 1) {
                $this->logger->debug('Ignoring file (match): ' . $file->getFilename());
                $this->logger->dump($matches, \ilLogLevel::DEBUG);
                continue;
            }
            $element = $this->import_handler->schema()->info()->handler()
                ->withFilePath($file->getPathname())
                ->withComponent((string) $matches[1])
                ->withSubtype((string) $matches[3])
                ->withVersion(new Version($matches[4] . (($matches[6] ?? '') ? '.' . $matches[6] : '')));
            $this->collection = $this->collection
                ->withElement($element);
            $this->logger->debug($file->getFilename() . ' matches');
        }
    }
}
