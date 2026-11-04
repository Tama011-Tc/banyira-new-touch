<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_each_role_reaches_only_its_workspace(): void
    {
        $matrix = [
            'credit_officer' => ['/internal/credit' => 200, '/internal/finance' => 403, '/internal/it' => 403, '/internal/approvals' => 403],
            'it_admin' => ['/internal/it' => 200, '/internal/rules' => 200, '/internal/finance' => 403, '/internal/approvals' => 403, '/internal/finance/payroll' => 403],
            'sales_marketing' => ['/internal/sales' => 200, '/internal/credit' => 403, '/internal/finance' => 403],
            'finance_officer' => ['/internal/finance' => 200, '/internal/finance/payroll' => 200, '/internal/credit' => 403, '/internal/approvals' => 403],
            'executive' => ['/internal/executive' => 200, '/internal/approvals' => 200, '/internal/finance' => 200, '/internal/finance/funding' => 403, '/internal/finance/approvals' => 403],
        ];
        foreach ($matrix as $role => $urls) {
            $u = $this->staff($role);
            foreach ($urls as $url => $status) {
                $this->asStaff($u)->get($url)->assertStatus($status);
            }
        }
    }

    public function test_business_and_investor_areas_are_separate(): void
    {
        $this->actingAs($this->business())->get('/investor')->assertForbidden();
        $this->actingAs($this->investor())->get('/business')->assertForbidden();
    }

    public function test_a_business_cannot_see_another_business_application(): void
    {
        $a = $this->business();
        $b = $this->business();
        $app = $this->submittedApplication($a);

        $this->actingAs($b)->get("/business/applications/{$app->id}")->assertForbidden();
        $this->actingAs($a)->get("/business/applications/{$app->id}")->assertOk();
    }

    public function test_it_role_cannot_be_given_approval_powers(): void
    {
        $it = $this->staff('it_admin');
        $role = Role::where('slug', 'it_admin')->first();
        $other = $this->staff('it_admin');   // an IT admin cannot edit their own role, so use the executive role holder test below
        $exec = $this->staff('executive');

        // An IT admin editing their own role is refused.
        $this->asStaff($it)->put("/internal/it/roles/{$role->id}", ['permissions' => $role->permissions()->pluck('permissions.id')->all()])->assertSessionHasErrors('permissions');

        // Nobody can grant finance approval to the IT role.
        $execRole = Role::where('slug', 'executive')->first();
        $execRole->permissions()->attach(Permission::where('slug', 'roles.manage')->value('id'));
        $this->asStaff($exec->refresh())->put("/internal/it/roles/{$role->id}", ['permissions' => [Permission::where('slug', 'finance.approve')->value('id')]])->assertSessionHasErrors('permissions');
        $this->assertFalse($other->refresh()->hasPermission('finance.approve'));
    }
}
