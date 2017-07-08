<?php

/**
 * This file is part of the pimcore-sitemap-plugin package.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Byng\Pimcore\Sitemap\Generator;

use Pimcore\Config;
use Pimcore\Model\Document;
use Byng\Pimcore\Sitemap\Gateway\DocumentGateway;
use Byng\Pimcore\Sitemap\Notifier\GoogleNotifier;
use SimpleXMLElement;

use Pimcore\View\Helper\Url;


/**
 * Sitemap Generator
 *
 * @author Ioannis Giakoumidis <ioannis@byng.co>
 */
final class SitemapGenerator
{
    /**
     * @var string
     */
    private $hostUrl;

    /**
     * @var SimpleXMLElement
     */
    private $xml;

    /**
     * @var DocumentGateway
     */
    private $documentGateway;


    /**
     * SitemapGenerator constructor.
     */
    public function __construct()
    {
        $this->hostUrl = Config::getSystemConfig()->get("general")->get("domain");
        $this->documentGateway = new DocumentGateway();

        $this->newXml();
    }

    private function newXml() {
        $this->xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'
        );
    }

    private function newIndexXml() {
        $this->xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>'
        );
    }

    public function generateXml()
    {
        $this->generateDocumentsXml();
        $this->generateObjectsXml();
        $this->generateIndexXml();

        if (Config::getSystemConfig()->get("general")->get("environment") === "production") {
            $this->notifySearchEngines();
        }
    }

    /**
     * Generates the sitemap-documents.xml file
     *
     * @return void
     */
    public function generateDocumentsXml()
    {
        // Get all the root elements with parentId '1'
        $rootDocuments = $this->documentGateway->getChildren(1);

        foreach ($rootDocuments as $rootDocument) {
            $this->addDocumentUrlChild($rootDocument);
            $this->listAllChildren($rootDocument);
        }
        $this->xml->asXML(PIMCORE_DOCUMENT_ROOT . "/sitemap-documents.xml");

    }

    public function generateObjectsXml()
    {
        if (defined("SITEMAP_OBJECTS")) {
            foreach (SITEMAP_OBJECTS as $name => $route) {
                $this->newXml();
                $objectClass = "\Pimcore\Model\Object\\{$name}";
                $objects = $objectClass::getList();
                foreach ($objects as $object) {
                    $this->addObjectUrlChild($object, $route);
                }
                $lowercaseName = strtolower($name);
                $this->xml->asXML(PIMCORE_DOCUMENT_ROOT . "/sitemap-{$lowercaseName}s.xml");
            }
        }
    }

    public function generateIndexXml()
    {
        $this->newIndexXml();
        $lastMod = new \DateTime();

        $url = $this->xml->addChild("sitemap");
        $url->addChild('loc', $this->hostUrl . "/sitemap-index.xml");
        $url->addChild('lastmod', $this->getDateFormat($lastMod->getTimestamp()));

        $url = $this->xml->addChild("sitemap");
        $url->addChild('loc', $this->hostUrl . "/sitemap-documents.xml");
        $url->addChild('lastmod', $this->getDateFormat($lastMod->getTimestamp()));


        if (defined("SITEMAP_OBJECTS")) {
            foreach (SITEMAP_OBJECTS as $name => $route) {
                $url = $this->xml->addChild("sitemap");
                $lowercaseName = strtolower($name);
                $url->addChild('loc', $this->hostUrl . "/sitemap-{$lowercaseName}s.xml");
                $url->addChild('lastmod', $this->getDateFormat($lastMod->getTimestamp()));
            }
        }
        $this->xml->asXML(PIMCORE_DOCUMENT_ROOT . "/sitemap.xml");
        $this->xml->asXML(PIMCORE_DOCUMENT_ROOT . "/sitemap-index.xml");

    }

    /**
     * Finds all the children of a document recursively
     *
     * @param Document $document
     * @return void
     */
    private function listAllChildren(Document $document)
    {
        $children = $this->documentGateway->getChildren($document->getId());

        foreach ($children as $child) {
            $this->addDocumentUrlChild($child);
            $this->listAllChildren($child);
        }
    }

    /**
     * Adds a url child in the xml file.
     *
     * @param Document $document
     * @return void
     */
    private function addDocumentUrlChild(Document $document)
    {
        if (
            $document instanceof Document\Page &&
            !$document->getProperty("sitemap_exclude")
        ) {
            echo $this->hostUrl . $document->getFullPath() . "\n";
            $url = $this->xml->addChild("url");
            $url->addChild('loc', $this->hostUrl . $document->getFullPath());
            $url->addChild('lastmod', $this->getDateFormat($document->getModificationDate()));
        }
    }

    private function addObjectUrlChild($object, $route)
    {
        // if (!$object->getProperty("sitemap_exclude")) {

            $url = $this->xml->addChild("url");

            $urlHelper = new Url();
            $route = $urlHelper->url(['key' => $object->getKey()], $route, true);
            echo $this->hostUrl . $route . "\n";
            $url->addChild('loc', $this->hostUrl . $route);
            $url->addChild('lastmod', $this->getDateFormat($object->getModificationDate()));
        // }
    }
    /**
     * Format a given date.
     *
     * @param $date
     * @return string
     */
    private function getDateFormat($date)
    {
        return gmdate(DATE_ATOM, $date);
    }

    /**
     * Notify search engines about the sitemap update.
     *
     * @return void
     */
    private function notifySearchEngines()
    {
        $googleNotifier = new GoogleNotifier();

        if ($googleNotifier->notify()) {
            echo "Google has been notified \n";
        } else {
            echo "Google has not been notified \n";
        }
    }
}
