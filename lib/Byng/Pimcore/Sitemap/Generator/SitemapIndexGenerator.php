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
use Byng\Pimcore\Sitemap\Generator\BaseGenerator;
use Byng\Pimcore\Sitemap\Generator\SitemapDocumentsGenerator;
use Byng\Pimcore\Sitemap\Generator\SitemapObjectsGenerator;
use Byng\Pimcore\Sitemap\Notifier\GoogleNotifier;
use SimpleXMLElement;
use Pimcore\Model\Site;


/**
 * Sitemap Generator
 *
 * @author Ioannis Giakoumidis <ioannis@byng.co>
 */
final class SitemapIndexGenerator extends BaseGenerator
{
    protected function newXmlDocument() {
        $this->xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>'
        );
    }

    public function generateXml()
    {
        $lastMod = new \DateTime();

        $url = $this->xml->addChild("sitemap");
        $url->addChild('loc', $this->hostUrl . "/sitemap-index.xml");
        $url->addChild('lastmod', $this->getDateFormat($lastMod->getTimestamp()));

        $url = $this->xml->addChild("sitemap");
        $url->addChild('loc', $this->hostUrl . "/sitemap-documents.xml");
        $url->addChild('lastmod', $this->getDateFormat($lastMod->getTimestamp()));


        if (defined('SITEMAP_OBJECTS')) {
            if ($this->site && $this->site->getRootDocument()->getKey() === 'battersea-power-station') {

                foreach (SITEMAP_OBJECTS as $name => $route) {
                    $url = $this->xml->addChild('sitemap');
                    $lowercaseName = strtolower($name);
                    $url->addChild('loc', $this->hostUrl . "/sitemap-{$lowercaseName}s.xml");
                    $url->addChild('lastmod', $this->getDateFormat($lastMod->getTimestamp()));
                }
            }
        }
        $this->xml->asXML($this->sitemapPath('/sitemap.xml'));
        $this->xml->asXML($this->sitemapPath('/sitemap-index.xml'));

    }

    /**
     * Notify search engines about the sitemap update.
     *
     * @return void
     */
    protected function notifySearchEngines()
    {
        $googleNotifier = new GoogleNotifier();

        if ($googleNotifier->notify()) {
            echo "Google has been notified \n";
        } else {
            echo "Google has not been notified \n";
        }
    }
}
