<?php

declare(strict_types=1);

namespace Osmium\Services\TradeLacrm\Models;

use Osmium\Core\Library\OsmiumPDO;
use Osmium\Core\Library\ServiceHooks;
use Osmium\Core\Library\ServiceLoader;
use Osmium\Core\Models\ServiceModel;

/**
 * The hook entry points for the LACRM sync (see service.json): they build a TradeLacrmAdapter or TradeLacrmImport wired to
 * the real hooks. Customers are read and the selected source is learned through Trade Accounts' hooks (trade.customer.lookup,
 * trade.source.selected), never its PHP, and nothing here calls the LACRM service's PHP either.
 */
final class TradeLacrmHooks
{
    private const SERVICE_ID = 'osmium-lacrm';

    /** trade.sources: offered as a customer source while the LACRM service is installed and enabled. */
    public static function source(array $payload): ?array
    {
        $db = $payload['dataSource'] ?? null;
        $noDatabase = !($db instanceof OsmiumPDO);
        if ($noDatabase) return null;

        $installed = (new ServiceLoader(new ServiceModel($db)))->isEnabled(self::SERVICE_ID);
        if (!$installed) return null;

        return [
            'id' => TradeLacrmMapper::SOURCE,
            'label' => 'Less Annoying CRM',
            'ready' => true,
            'settingsRoute' => 'admin/settings/lacrm',
            'importRoute' => 'admin/trade/lacrm-import',
        ];
    }

    public static function formSubmitted(array $payload): void
    {
        self::adapter($payload)?->formSubmitted($payload);
    }

    public static function saleWon(array $payload): void
    {
        self::adapter($payload)?->saleWon($payload);
    }

    public static function saleLost(array $payload): void
    {
        self::adapter($payload)?->saleLost($payload);
    }

    public static function contactUpdated(array $payload): void
    {
        self::adapter($payload)?->contactUpdated($payload);
    }

    /** The bulk import, or null when LACRM is not the selected source. Errors from LACRM reach the caller. */
    public static function importer(OsmiumPDO $db): ?TradeLacrmImport
    {
        $ask = self::ask($db);
        $notSelected = (string) $ask('trade.source.selected', []) !== TradeLacrmMapper::SOURCE;
        if ($notSelected) return null;

        return new TradeLacrmImport(
            ask: $ask,
            announce: self::announce($db),
            findByRef: static fn(string $ref): ?array => self::customerByRef($ask, $ref),
            emailOwner: static function (string $email) use ($ask): ?string {
                $holder = $ask('trade.customer.lookup', ['email' => $email]);
                $tiedElsewhere = \is_array($holder) && ($holder['source'] ?? null) !== null;

                return $tiedElsewhere ? (string) $holder['company_name'] : null;
            },
            check: static function (array $customer) use ($ask): ?string {
                $verdict = $ask('trade.customer.check', ['customer' => $customer]);
                $noAnswer = !\is_array($verdict);
                if ($noAnswer) return 'Trade Accounts did not answer, so the details could not be checked';

                return !empty($verdict['valid']) ? null : (string) ($verdict['problem'] ?? 'The details are not valid');
            },
        );
    }

    private static function adapter(array $payload): ?TradeLacrmAdapter
    {
        $db = $payload['dataSource'] ?? null;
        $noDatabase = !($db instanceof OsmiumPDO);
        if ($noDatabase) return null;

        $ask = self::ask($db);
        $selected = (string) $ask('trade.source.selected', []);
        $notSelected = $selected !== TradeLacrmMapper::SOURCE;
        if ($notSelected) return null; // nothing is asked of LACRM unless it is the chosen source

        return new TradeLacrmAdapter(
            selectedSource: $selected,
            ask: $ask,
            announce: self::announce($db),
            findByRef: static fn(string $ref): ?array => self::customerByRef($ask, $ref),
            log: static fn(string $message) => \error_log($message),
        );
    }

    /** @return callable(string, array): mixed */
    private static function ask(OsmiumPDO $db): callable
    {
        $hooks = new ServiceHooks(new ServiceLoader(new ServiceModel($db)));

        return static fn(string $question, array $asked): mixed => $hooks->handle($question, $asked + ['dataSource' => $db]);
    }

    /** @return callable(string, array): void */
    private static function announce(OsmiumPDO $db): callable
    {
        $hooks = new ServiceHooks(new ServiceLoader(new ServiceModel($db)));

        return static fn(string $event, array $told) => $hooks->dispatch($event, $told + ['dataSource' => $db]);
    }

    /** @param callable(string, array): mixed $ask */
    private static function customerByRef(callable $ask, string $ref): ?array
    {
        $customer = $ask('trade.customer.lookup', ['source' => TradeLacrmMapper::SOURCE, 'sourceRef' => $ref]);

        return \is_array($customer) ? $customer : null;
    }
}
