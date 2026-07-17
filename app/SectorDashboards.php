<?php
// SectorDashboards.php — put this straight in app/ (namespace App)

namespace App;

class SectorDashboards
{
    /**
     * Sector name (as stored in tenant.sectors.sector) => dashboard route name.
     *
     * Only sectors with a live route group in web.php belong here. The other
     * five seeded in the sectors table (Consultancy, IT, Healthcare,
     * Hospitality, Properties) don't have controllers/routes yet — add a
     * line the day each one does. Anything not listed here is automatically
     * filtered out of default_landing_sector options and employee_access
     * lookups, so an Admin/Operations user can never get redirected to a
     * dashboard that doesn't exist.
     */
    public static function routes(): array
    {
        return [
            'Retail'    => 'retail.operations.dashboard',
            'Wholesale' => 'wholesale.operations.dashboard',
            'Finance'   => 'finance.operations.dashboard',

            // Not yet built — uncomment once the route/controller exists:
            // 'Consultancy' => 'consultancy.operations.dashboard',
            // 'IT'          => 'it.operations.dashboard',
            // 'Healthcare'  => 'healthcare.operations.dashboard',
            // 'Hospitality' => 'hospitality.operations.dashboard',
            // 'Properties'  => 'properties.operations.dashboard',
        ];
    }

    /**
     * Every sector currently seeded in tenant.sectors, live or not — handy
     * for admin UI that needs to show/manage all sectors rather than just
     * the ones with a working dashboard.
     */
    public static function all(): array
    {
        return [
            'Retail',
            'Wholesale',
            'Finance',
            'Consultancy',
            'IT',
            'Healthcare',
            'Hospitality',
            'Properties',
        ];
    }
}