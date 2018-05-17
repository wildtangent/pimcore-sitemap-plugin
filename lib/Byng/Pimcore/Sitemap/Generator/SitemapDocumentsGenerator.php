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

use Pimcore\Model\Document;
use Byng\Pimcore\Sitemap\Generator\BaseGenerator;
use Byng\Pimcore\Sitemap\Gateway\DocumentGateway;
use SimpleXMLElement;

use Pimcore\View\Helper\Url;
use Pimcore\Model\Site;


/**
 * Sitemap Generator
 *
 * @author Ioannis Giakoumidis <ioannis@byng.co>
 */
final class SitemapDocumentsGenerator extends BaseGenerator
{
    /**
     * @var DocumentGateway
     */
    private $documentGateway;


    /**
     * SitemapGenerator constructor.
     */
    public function __construct($site = null)
    {
        parent::__construct($site);
        $this->documentGateway = new DocumentGateway();
    }

    protected function newXmlDocument() {
        $this->xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'
        );
    }

    public function generateXml()
    {
        // Get all the root elements with parentId '1'
        $rootDocuments = $this->documentGateway->getChildren($this->rootId);
        foreach ($rootDocuments as $rootDocument) {
            $this->addUrlChild($rootDocument);
            $this->listAllChildren($rootDocument);
        }
        $this->xml->asXML($this->sitemapPath('/sitemap-documents.xml'));
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
            $this->addUrlChild($child);
            $this->listAllChildren($child);
        }
    }

    /**
     * Adds a url child in the xml file.
     *
     * @param Document $document
     * @return void
     */
    private function addUrlChild(Document $document)
    {
        if (
            $document instanceof Document\Page &&
            !$document->getProperty("sitemap_exclude")
        ) {
            echo $this->hostUrl . $document->getFullPath() . "\n";
            $url = $this->xml->addChild("url");
            $path = $document->getFullPath();
            dump($this->site->getRootPath());

            if ($this->site) {
                $path = preg_replace("#{$this->site->getRootPath()}#",'', $path);
            }

            $url->addChild('loc', $this->hostUrl . $path);
            $url->addChild('lastmod', $this->getDateFormat($document->getModificationDate()));
        }
    }
}
