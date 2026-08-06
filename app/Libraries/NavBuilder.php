<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

use Config\Navigation;

/**
 * Filters the declarative Navigation::tree() down to what one request's actor may
 * see, and marks the active trail. One builder drives every portal — the sidebar,
 * the mobile drawer, breadcrumbs and the command palette all consume build().
 *
 * Visibility is computed from PERMISSIONS (role-resources / plan / module), never a
 * hardcoded role string, so a custom staff role composes correctly. A group with no
 * visible items is dropped entirely (never an empty header). An item whose children
 * all filter out but which itself is reachable stays as a plain leaf.
 */
class NavBuilder
{
    private string $userType;
    private array $resources;
    private string $currentPath;   // e.g. "erp/staff-list" (no leading slash)

    /**
     * @param string $userType    'company' | 'staff' | 'super_user'
     * @param array  $resources   staff_role_resource() output (ignored for non-staff)
     * @param string $currentPath the current request URI path, slash-trimmed
     */
    public function __construct(string $userType, array $resources, string $currentPath)
    {
        $this->userType    = $userType;
        $this->resources   = $resources;
        $this->currentPath = trim($currentPath, '/');
    }

    /** Build the filtered, active-marked group list for this actor. */
    public function build(): array
    {
        $out = [];
        foreach (Navigation::tree() as $group) {
            if (! $this->roleAllows($group)) {
                continue;
            }
            $items = [];
            foreach ($group['items'] as $item) {
                $built = $this->buildItem($item);
                if ($built !== null) {
                    $items[] = $built;
                }
            }
            if ($items === []) {
                continue; // group with zero visible items hides entirely
            }
            $out[] = [
                'id'     => $group['id'],
                'label'  => $group['label'],
                'order'  => $group['order'] ?? 999,
                'active' => $this->anyActive($items),
                'items'  => $items,
            ];
        }
        usort($out, static fn ($a, $b) => $a['order'] <=> $b['order']);
        return $out;
    }

    private function buildItem(array $item): ?array
    {
        if (! $this->visible($item)) {
            return null;
        }

        $children = [];
        foreach ($item['children'] ?? [] as $child) {
            if ($this->visible($child)) {
                $href = $child['href'];
                $children[] = [
                    'id'     => $child['id'],
                    'label'  => $child['label'],
                    'href'   => $href,
                    'active' => $this->isActive($href),
                ];
            }
        }

        $href      = $item['href'];
        $selfActive = $this->isActive($href);
        $childActive = false;
        foreach ($children as $c) {
            $childActive = $childActive || $c['active'];
        }

        return [
            'id'          => $item['id'],
            'label'       => $item['label'],
            'icon'        => $item['icon'] ?? 'circle',
            'href'        => $href,
            'external'    => ! empty($item['external']),
            'children'    => $children,
            'active'      => $selfActive || $childActive,
            'childActive' => $childActive,
        ];
    }

    /** Group-level role gate. */
    private function roleAllows(array $node): bool
    {
        if (! isset($node['roles'])) {
            return true;
        }
        return in_array($this->userType, $node['roles'], true);
    }

    /**
     * Full visibility test for an item or child: role, plan tier, tenant module
     * toggle, and staff role-resource. company/super_user bypass resource checks.
     */
    private function visible(array $node): bool
    {
        if (! $this->roleAllows($node)) {
            return false;
        }
        if (isset($node['plan']) && function_exists('plan_allows') && ! plan_allows($node['plan'])) {
            return false;
        }
        if (isset($node['module']) && ! $this->moduleEnabled($node['module'])) {
            return false;
        }
        if (isset($node['resources']) && $this->userType === 'staff') {
            if (array_intersect($node['resources'], $this->resources) === []) {
                return false;
            }
        }
        return true;
    }

    private function moduleEnabled(string $key): bool
    {
        static $modules = null;
        if ($modules === null) {
            $modules = [];
            if (function_exists('erp_company_settings')) {
                $cs = erp_company_settings() ?: [];
                $raw = $cs['setup_modules'] ?? '';
                $decoded = @unserialize((string) $raw);
                if (is_array($decoded)) {
                    $modules = $decoded;
                }
            }
        }
        return isset($modules[$key]) && (int) $modules[$key] === 1;
    }

    /** Leaf-active = exact path match; parents inherit via childActive. */
    private function isActive(string $href): bool
    {
        return trim($href, '/') === $this->currentPath;
    }

    private function anyActive(array $items): bool
    {
        foreach ($items as $i) {
            if ($i['active']) {
                return true;
            }
        }
        return false;
    }
}
