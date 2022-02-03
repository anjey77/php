<?php
/**
 * Project: Core
 * User: jing
 * Date: 2/9/19
 * Time: 12:09 pm
 */
namespace Omnyfy\Core\Model\Mail;

use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Zend\Mime\Part as MimePart;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    private $parts;
    public function addAttachment($filePath, $fileNameInEmail, $ics = null)
    {
        $type = strtolower($this->loadFileType($filePath));

        switch($type) {
            case 'pdf':
                $this->message->createAttachment(
                    file_get_contents($filePath),
                    'application/pdf',
                    \Zend_Mime::DISPOSITION_ATTACHMENT,
                    \Zend_Mime::ENCODING_BASE64,
                    $fileNameInEmail
                );
                break;
            case 'csv':
                $this->message->createAttachment(
                    file_get_contents($filePath),
                    'text/csv',
                    \Zend_Mime::DISPOSITION_INLINE,
                    \Zend_Mime::ENCODING_BASE64,
                    $fileNameInEmail
                );
                break;
            case 'ics':
                $this->message->createAttachment(
                    $ics,
                    'text/calendar',
                    \Zend_Mime::DISPOSITION_ATTACHMENT,
                    \Zend_Mime::ENCODING_BASE64,
                    $fileNameInEmail);
                break;
        }

        return $this;
    }

    public function addContentAttachment($content, $type, $nameInEmail, $disposition=\Zend_Mime::DISPOSITION_ATTACHMENT)
    {
        $this->message->createAttachment(
            $content,
            $type,
            $disposition,
            \Zend_Mime::ENCODING_BASE64,
            $nameInEmail
        );

        return $this;
    }

    protected function loadFileType($filePath)
    {
        return pathinfo($filePath, PATHINFO_EXTENSION);
    }

    protected function prepareMessage()
    {
        $template = $this->getTemplate();
        $types = [
            TemplateTypesInterface::TYPE_TEXT => MessageInterface::TYPE_TEXT,
            TemplateTypesInterface::TYPE_HTML => MessageInterface::TYPE_HTML,
        ];

        $body = $template->processTemplate();
        $this->message->setMessageType($types[$template->getType()])
            ->setBody($body)
            ->setSubject(html_entity_decode($template->getSubject(), ENT_QUOTES));

        if (array_key_exists('attachments', $this->templateVars)) {
            $attachments = $this->templateVars['attachments'];
            if (!empty($attachments)) {
                foreach($attachments as $attachment) {
                    if (!array_key_exists('content', $attachment)
                        || !array_key_exists('type', $attachment)
                        || !array_key_exists('name', $attachment)
                    ) {
                        continue;
                    }
                    $disposition = isset($attachment['disposition']) ? $attachment['disposition'] : \Zend_Mime::DISPOSITION_ATTACHMENT;
                    if ($disposition !== \Zend_Mime::DISPOSITION_ATTACHMENT && $disposition !== \Zend_Mime::DISPOSITION_INLINE) {
                        $disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
                    }
                    $this->addCustomAttachment(
                        $attachment['content'],
                        $attachment['name'],
                        $attachment['type']
                    );
                }
            }
        }
        if (!empty($this->parts)) {
            foreach ($this->parts as $part) {
                if (!is_null($this->message->getBody())) {
                    $this->message->getBody()->addPart($part);
                }
            }
            if (!is_null($this->message->getBody())) {
                $this->message->setBody($this->message->getBody());
            }
        }
        $this->parts = [];
        return $this;
    }

    public function addCustomAttachment($file_content, $name, $file_type = "application/pdf") {
        if (!empty($file_content) && !empty($name)) {
            $attach = new MimePart($file_content);
            $attach->setType($file_type);
            $attach->setDisposition(\Zend_Mime::DISPOSITION_ATTACHMENT);
            $attach->setEncoding(\Zend_Mime::ENCODING_BASE64);
            $attach->setFileName(basename($name));
            $attach->setId($name);
            $this->parts[] = $attach;
        }

    }
}
