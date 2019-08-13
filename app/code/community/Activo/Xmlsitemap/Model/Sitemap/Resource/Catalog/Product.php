<?php
class Activo_Xmlsitemap_Model_Sitemap_Resource_Catalog_Product extends Mage_Sitemap_Model_Resource_Catalog_Product
{
    /**
     * Get category collection array
     *
     * @param unknown_type $storeId
     * @return array
     */
    public function getCollection($storeId)
    {
        $products = array();

        $store = Mage::app()->getStore($storeId);
        /* @var $store Mage_Core_Model_Store */

        if (!$store) {
            return false;
        }

        $urCondions = array(
            'e.entity_id=ur.product_id',
            'ur.category_id IS NULL',
            $this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
            $this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
        );
        $this->_select = $this->_getWriteAdapter()->select()
            ->from(array('e' => $this->getMainTable()), array($this->getIdFieldName(), 'created_at', 'updated_at'))
            ->join(
                array('w' => $this->getTable('catalog/product_website')),
                'e.entity_id=w.product_id',
                array()
            )
            ->where('w.website_id=?', $store->getWebsiteId())
            ->joinLeft(
                array('ur' => $this->getTable('core/url_rewrite')),
                join(' AND ', $urCondions),
                array('url' => 'request_path')
            );

        $this->_addFilter($storeId, 'visibility', Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds(), 'in');
        $this->_addFilter($storeId, 'status', Mage::getSingleton('catalog/product_status')->getVisibleStatusIds(), 'in');

        $query = $this->_getWriteAdapter()->query($this->_select);
        while ($row = $query->fetch()) {
            $product = $this->_prepareProduct($row);
            $products[$product->getId()] = $product;
        }

        return $products;
    }

    /**
     * Prepare product
     *
     * @param array $productRow
     * @return Varien_Object
     */
    protected function _prepareProduct(array $productRow)
    {
        $product = new Varien_Object();
        $product->setId($productRow[$this->getIdFieldName()]);
        $productUrl = !empty($productRow['url']) ? $productRow['url'] : 'catalog/product/view/id/' . $product->getId();
        $product->setUrl($productUrl);
        $product->setCreatedAt($productRow['created_at']);
        $product->setUpdatedAt($productRow['updated_at']);
        return $product;
    }
}
