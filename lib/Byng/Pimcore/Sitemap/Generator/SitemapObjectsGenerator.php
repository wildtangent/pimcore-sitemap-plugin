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
use SimpleXMLElement;

use Pimcore\View\Helper\Url;


/**
 * Sitemap Generator
 *
 * @author Ioannis Giakoumidis <ioannis@byng.co>
 */
final class SitemapObjectsGenerator extends BaseGenerator
{
    protected function newXmlDocument() {
        $this->xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'
        );
    }

    public function generateXml()
    {
        if (defined('SITEMAP_OBJECTS')) {
            foreach (SITEMAP_OBJECTS as $name => $route) {
                $this->newXmlDocument();
                $objectClass = "\Pimcore\Model\Object\\{$name}";
                $objects = $objectClass::getList();
                foreach ($objects as $object) {
                    $this->addUrlChild($object, $route);
                }
                $lowercaseName = strtolower($name);
                $this->xml->asXML($this->sitemapPath("/sitemap-{$lowercaseName}s.xml"));
            }
        }
    }

    private function addUrlChild($object, $route)
    {
        if (!$object->getProperty('sitemap_exclude')) {

            $url = $this->xml->addChild('url');

            $urlHelper = new Url();
            $route = $urlHelper->url(['key' => $object->getKey()], $route, true);
            echo $this->hostUrl . $route . "\n";
            $url->addChild('loc', $this->hostUrl . $route);
            $url->addChild('lastmod', $this->getDateFormat($object->getModificationDate()));
        }
    }
}
