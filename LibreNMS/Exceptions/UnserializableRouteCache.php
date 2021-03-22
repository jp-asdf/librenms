<?php
/**
 * UnserializableRouteCache.php
 *
 * This error is caused when the route cache is generated by an newer version of PHP than the one that loads it
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 * @copyright  2020 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace LibreNMS\Exceptions;

use LibreNMS\Interfaces\Exceptions\UpgradeableException;
use Throwable;

class UnserializableRouteCache extends \Exception implements UpgradeableException
{
    protected $cli_php_version;
    protected $web_php_version;

    public function __construct($message = '', $code = 0, Throwable $previous = null, $cli_php_version = null, $web_php_version = null)
    {
        $this->cli_php_version = $cli_php_version;
        $this->web_php_version = $web_php_version;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Try to convert the given Exception to this exception
     *
     * @param \Exception $exception
     * @return static
     */
    public static function upgrade($exception)
    {
        $errorMessage = "Erroneous data format for unserializing 'Symfony\Component\Routing\CompiledRoute'";
        if ($exception instanceof \ErrorException && $exception->message == $errorMessage) {
            $cli = rtrim(shell_exec('php -r "echo PHP_VERSION;"'));
            if (version_compare($cli, PHP_VERSION, '!=')) {
                return new static($exception->getMessage(), $exception->getCode(), $exception, $cli, PHP_VERSION);
            }
        }

        return null;
    }

    /**
     * Render the exception into an HTTP or JSON response.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render(\Illuminate\Http\Request $request)
    {
        $title = trans('exceptions.unserializable_route_cache.title');
        $message = trans('exceptions.unserializable_route_cache.message', ['cli_version' => $this->cli_php_version, 'web_version' => $this->web_php_version]);

        return $request->wantsJson() ? response()->json([
            'status' => 'error',
            'message' => "$title: $message",
        ]) : response()->view('errors.generic', [
            'title' => $title,
            'content' => $message,
        ]);
    }
}