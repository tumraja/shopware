<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\StoreFrontBundle\Property;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\StoreFrontBundle\Common\FieldHelper;
use Shopware\Context\TranslationContext;

/**
 * @category  Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class PropertyGateway
{
    /**
     * Constant for the alphanumeric sort configuration of the category filters
     */
    const FILTERS_SORT_ALPHANUMERIC = 0;

    /**
     * Constant for the numeric sort configuration of the category filters
     */
    const FILTERS_SORT_NUMERIC = 1;

    /**
     * Constant for the position sort configuration of the category filters
     */
    const FILTERS_SORT_POSITION = 3;

    /**
     * @var PropertyHydrator
     */
    private $propertyHydrator;

    /**
     * The FieldHelper class is used for the
     * different table column definitions.
     *
     * This class helps to select each time all required
     * table data for the store front.
     *
     * Additionally the field helper reduce the work, to
     * select in a second step the different required
     * attribute tables for a parent table.
     *
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $connection;

    /**
     * @param Connection                  $connection
     * @param FieldHelper                 $fieldHelper
     * @param PropertyHydrator            $propertyHydrator
     * @param \Shopware_Components_Config $config
     */
    public function __construct(
        Connection $connection,
        FieldHelper $fieldHelper,
        PropertyHydrator $propertyHydrator
    ) {
        $this->propertyHydrator = $propertyHydrator;
        $this->connection = $connection;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * The \Shopware\Bundle\StoreFrontBundle\Property\PropertySet requires the following data:
     * - Property set data
     * - Property groups data
     * - Property options data
     * - Core attribute of the property set
     *
     * Required translation in the provided context language:
     * - Property set
     * - Property groups
     * - Property options
     *
     * Required conditions for the selection:
     * - Selects only values which ids provided
     * - Property values has to be sorted by the \Shopware\Bundle\StoreFrontBundle\Property\PropertySet sort mode.
     *  - Sort mode equals to 1, the values are sorted by the numeric value
     *  - Sort mode equals to 3, the values are sorted by the position
     *  - In all other cases the values are sorted by their alphanumeric value
     *
     * @param int[]              $valueIds
     * @param \Shopware\Context\TranslationContext $context
     *
     * @return \Shopware\Bundle\StoreFrontBundle\Property\PropertySet[] Each array element (set, group, option) is indexed by his id
     */
    public function getList(array $valueIds, TranslationContext $context)
    {
        $query = $this->connection->createQueryBuilder();

        $query
            ->addSelect('relations.position as __relations_position')
            ->addSelect($this->fieldHelper->getPropertySetFields())
            ->addSelect($this->fieldHelper->getPropertyGroupFields())
            ->addSelect($this->fieldHelper->getPropertyOptionFields())
            ->addSelect($this->fieldHelper->getMediaFields())
        ;

        $query->from('s_filter', 'propertySet')
            ->innerJoin('propertySet', 's_filter_relations', 'relations', 'relations.groupID = propertySet.id')
            ->leftJoin('propertySet', 's_filter_attributes', 'propertySetAttribute', 'propertySetAttribute.filterID = propertySet.id')
            ->innerJoin('relations', 's_filter_options', 'propertyGroup', 'relations.optionID = propertyGroup.id AND filterable = 1')
            ->leftJoin('propertyGroup', 's_filter_options_attributes', 'propertyGroupAttribute', 'propertyGroupAttribute.optionID = propertyGroup.id')
            ->innerJoin('propertyGroup', 's_filter_values', 'propertyOption', 'propertyOption.optionID = propertyGroup.id')
            ->leftJoin('propertyOption', 's_filter_values_attributes', 'propertyOptionAttribute', 'propertyOptionAttribute.valueID = propertyOption.id')
            ->leftJoin('propertyOption', 's_media', 'media', 'propertyOption.media_id = media.id')
            ->leftJoin('media', 's_media_attributes', 'mediaAttribute', 'mediaAttribute.mediaID = media.id')
            ->leftJoin('media', 's_media_album_settings', 'mediaSettings', 'mediaSettings.albumID = media.albumID')
            ->where('propertyOption.id IN (:ids)')
            ->groupBy('propertyOption.id')
            ->orderBy('propertySet.position')
            ->setParameter(':ids', $valueIds, Connection::PARAM_INT_ARRAY);

        $this->fieldHelper->addMediaTranslation($query, $context);
        $this->fieldHelper->addPropertySetTranslation($query, $context);
        $this->fieldHelper->addPropertyGroupTranslation($query, $context);
        $this->fieldHelper->addPropertyOptionTranslation($query, $context);

        /** @var $statement \Doctrine\DBAL\Driver\ResultStatement */
        $statement = $query->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $result = $this->propertyHydrator->hydrateValues($rows);

        return $result;
    }
}
