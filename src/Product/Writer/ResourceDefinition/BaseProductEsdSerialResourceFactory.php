<?php declare(strict_types=1);

namespace Shopware\Product\Writer\ResourceDefinition;

use Shopware\Framework\Api\ApiFieldFactory;
use Shopware\Framework\Api\FieldBuilder;
use Shopware\Framework\Api\ApiFieldTemplate\BooleanType;
use Shopware\Framework\Api\ApiFieldTemplate\DateType;
use Shopware\Framework\Api\ApiFieldTemplate\FKType;
use Shopware\Framework\Api\ApiFieldTemplate\LongTextType;
use Shopware\Framework\Api\ApiFieldTemplate\LongTextWithHtmlType;
use Shopware\Framework\Api\ApiFieldTemplate\NowDefaultValueTemplate;
use Shopware\Framework\Api\ApiFieldTemplate\PKType;
use Shopware\Framework\Api\ApiFieldTemplate\TextType;
use Shopware\Framework\Api\ApiFieldTemplate\IntType;
use Shopware\Framework\Api\ApiFieldTemplate\FloatType;
use Shopware\Framework\Api\UuidGenerator\RamseyGenerator;

class BaseProductEsdSerialResourceFactory extends ApiFieldFactory
{
    public function getUuidGeneratorClass(): string
    {
        return RamseyGenerator::class;    
    }

    public function getTableName(): string
    {
        return 'product_esd_serial';    
    }
    
    public function getResourceName(): string
    {
        return 'ProductEsdSerial';    
    }

    protected function build(FieldBuilder $builder): FieldBuilder
    {
        return $builder
            ->start()
            ->add('uuid')
            ->setWritable('uuid')
            ->fromTemplate(TextType::class)
            ->setPrimary()
            ->setRequired()
        ->add('esdId')
            ->setWritable('esd_id')
            ->fromTemplate(IntType::class)
        ->add('productEsdUuid')
            ->setWritable('product_esd_uuid')
            ->fromTemplate(TextType::class)
            ->setRequired()
            ->setForeignKey('ProductEsd.uuid')
        ->add('serialNumber')
            ->setWritable('serial_number')
            ->fromTemplate(TextType::class)
            ->setRequired()
        ->add('productEsd')
            ->setVirtual('productEsdUuid', 'ProductEsd');
    }
}