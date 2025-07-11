<?php

declare(strict_types=1);

namespace HeyPongo\DeprecationMiddleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to signal HTTP resource deprecation according to RFC 9745 (Deprecation header),
 * with optional documentation link (RFC 8288) and Sunset header (RFC 8594).
 *
 * Usage:
 *   ->middleware('deprecated')
 *   ->middleware('deprecated:2024-06-01T00:00:00Z')
 *   ->middleware('deprecated:2024-06-01T00:00:00Z,https://doc.example.com/deprecation')
 *   ->middleware('deprecated:2024-06-01T00:00:00Z,https://doc.example.com/deprecation,2024-12-01T00:00:00Z')
 *   ->middleware('deprecated,,https://doc.example.com/deprecation,2024-12-01T00:00:00Z')
 *
 * @see https://www.rfc-editor.org/rfc/rfc9745 (Deprecation)
 * @see https://datatracker.ietf.org/doc/html/rfc8594 (Sunset)
 * @see https://www.rfc-editor.org/rfc/rfc8288 (Link header)
 */
final class DeprecatedRouteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $date  (optional) Deprecation date (HTTP-date or timestamp)
     * @param  string|null  $docUrl  (optional) Documentation URL for deprecation (Link rel="deprecation")
     * @param  string|null  $sunset  (optional) Sunset date (HTTP-date only, RFC 8594)
     */
    public function handle(Request $request, Closure $next, ?string $date = null, ?string $docUrl = null, ?string $sunset = null): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headerValue = 'true';
        $deprecationTimestamp = null;
        if (is_numeric($date)) {
            $headerValue = '@'.$date;
            $deprecationTimestamp = (int) $date;
        } elseif (! empty($date)) {
            $timestamp = strtotime($date);
            $headerValue = $timestamp !== false ? gmdate('D, d M Y H:i:s', $timestamp).' GMT' : $date;
            $deprecationTimestamp = $timestamp !== false ? $timestamp : null;
        }

        $response->headers->set('Deprecation', $headerValue);

        if ($docUrl) {
            $response->headers->set('Link', sprintf('<%s>; rel="deprecation"; type="text/html"', $docUrl));
        }

        if ($sunset) {
            $sunsetTimestamp = strtotime($sunset);
            if ($sunsetTimestamp === false) {
                Log::warning('Invalid Sunset date format for deprecated route: '.$sunset);
            } elseif ($deprecationTimestamp && $sunsetTimestamp < $deprecationTimestamp) {
                Log::warning('Sunset date ('.$sunset.') is before Deprecation date for deprecated route. Sunset header not set.');
            } else {
                $sunsetHeader = gmdate('D, d M Y H:i:s', $sunsetTimestamp).' GMT';
                $response->headers->set('Sunset', $sunsetHeader);
            }
        }

        return $response;
    }
}
