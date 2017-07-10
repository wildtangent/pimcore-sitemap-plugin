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
use Byng\Pimcore\Sitemap\Generator\SitemapGenerator;
use SimpleXMLElement;

/**
 * Sitemap Generator
 *
 * @author Ioannis Giakoumidis <ioannis@byng.co>
 */
class BaseGenerator
{
    /**
     * @var string
     */
    protected $hostUrl;

    /**
     * @var SimpleXMLElement
     */
    protected $xml;

    /**
     * BaseGenerator constructor.
     */
    public function __construct()
    {
        $this->hostUrl = "https://" . Config::getSystemConfig()->get("general")->get("domain");
        $this->newXmlDocument();
    }

    protected function newXmlDocument()
    {
        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>');
    }


    public function generateXml()
    {
        // Override in subclass
    }

    /**
     * Format a given date.
     *
     * @param $date
     * @return string
     */
    protected function getDateFormat($date)
    {
        return gmdate(DATE_ATOM, $date);
    }
}
