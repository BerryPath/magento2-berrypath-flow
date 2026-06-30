<?php

declare(strict_types=1);

namespace BerryPath\Flow\Controller\Feed;

use BerryPath\Flow\Model\Feed\Config as FeedConfig;
use BerryPath\Flow\Model\Feed\Formatter;
use BerryPath\Flow\Model\Feed\Generator;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;

class Id extends Action implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        private readonly FeedConfig $feedConfig,
        private readonly Generator $generator,
        private readonly Formatter $formatter
    ) {
        parent::__construct($context);
    }

    public function execute(): Raw
    {
        $storeId = $this->getStoreId();

        if (!$this->canGenerate($storeId)) {
            return $this->rawResponse('', 'application/xml; charset=UTF-8');
        }

        try {
            $feed = $this->generator->generate($storeId, $this->getOptionalParam('pid'));
            return $this->rawResponse($this->formatter->toXml($feed), 'application/xml; charset=UTF-8');
        } catch (\Throwable $exception) {
            return $this->rawResponse($exception->getMessage(), 'text/plain; charset=UTF-8', 500);
        }
    }

    private function getStoreId(): int
    {
        $storeId = (int)$this->getRequest()->getParam('id');
        if ($storeId > 0) {
            return $storeId;
        }

        $pathParts = explode('/', trim((string)$this->getRequest()->getPathInfo(), '/'));
        $lastPathPart = end($pathParts);

        return is_numeric($lastPathPart) ? (int)$lastPathPart : 0;
    }

    private function canGenerate(int $storeId): bool
    {
        return $storeId > 0
            && $this->feedConfig->isFeedEnabled($storeId);
    }

    private function getOptionalParam(string $key): ?string
    {
        $value = trim((string)$this->getRequest()->getParam($key));

        return $value !== '' ? $value : null;
    }

    private function rawResponse(string $content, string $contentType, int $statusCode = 200): Raw
    {
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode($statusCode);
        $result->setHeader('Content-Type', $contentType, true);
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('Pragma', 'no-cache', true);
        $result->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT', true);
        $result->setContents($content);

        return $result;
    }
}
