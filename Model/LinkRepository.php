<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 */
declare(strict_types=1);

namespace ScandiPWA\SalesGraphQl\Model;

use Magento\Downloadable\Model\LinkRepository as SourceLinkRepository;

class LinkRepository extends SourceLinkRepository {
    public function getListById(int $id): array
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->getById($id);
        return $this->getLinksByProduct($product);
    }
}
