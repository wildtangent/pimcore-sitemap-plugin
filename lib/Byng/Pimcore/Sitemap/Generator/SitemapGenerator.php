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
use Byng\Pimcore\Sitemap\Generator\SitemapIndexGenerator;
use Byng\Pimcore\Sitemap\Generator\SitemapDocumentsGenerator;
use Byng\Pimcore\Sitemap\Generator\SitemapObjectsGenerator;
use Byng\Pimcore\Sitemap\Notifier\GoogleNotifier;
use Pimcore\Model\Site;


/**
 * Sitemap Generator
 *
 * @author Ioannis Giakoumidis <ioannis@byng.co>
 */
final class SitemapGenerator
{
    /**
     * @var SitemapDocumentsGenerator
     */
    private $sitemapDocumentsGenerator;

    /**
     * @var SitemapObjectsGenerator
     */
    private $sitemapObjectsGenerator;


    /**
     * @var SitemapIndexGenerator
     */
    private $sitemapIndexGenerator;

    /**
     * @var Site
     */
    private $sites;

    /**
     * SitemapIndexGenerator constructor.
     */
    public function __construct()
    {
        $sites = new Site\Listing;
        $this->sites = $sites->load();
        $this->environment = Config::getSystemConfig()->get('general')->get('environment');
    }

    public function generateXml()
    {
        if (empty($this->sites)) {
            $this->sitemapIndexGenerator = new SitemapIndexGenerator();
            $this->sitemapDocumentsGenerator = new SitemapDocumentsGenerator();
            $this->sitemapObjectsGenerator = new SitemapObjectsGenerator();
            $this->sitemapIndexGenerator->generateXml();
            $this->sitemapDocumentsGenerator->generateXml();
            $this->sitemapObjectsGenerator->generateXml();
        } else {
            foreach($this->sites as $site) {
                $this->sitemapIndexGenerator = new SitemapIndexGenerator($site);
                $this->sitemapDocumentsGenerator = new SitemapDocumentsGenerator($site);
                $this->sitemapIndexGenerator->generateXml($site);
                $this->sitemapDocumentsGenerator->generateXml();

                // TODO: Mega Hack - should scope objects to sites?
                if($site->getRootDocument()->getKey() === 'battersea-power-station') {
                    $this->sitemapObjectsGenerator = new SitemapObjectsGenerator($site);
                    $this->sitemapObjectsGenerator->generateXml();
                }
            }
        }

        if ($this->environment === 'production') {
            $this->notifySearchEngines();
        }
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
