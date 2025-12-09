<?php

namespace Database\Seeders;

use AbuseIO\Models\Account;
use AbuseIO\Models\Contact;
use AbuseIO\Models\Domain;
use AbuseIO\Models\Event;
use AbuseIO\Models\Netblock;
use AbuseIO\Models\Note;
use AbuseIO\Models\Role;
use AbuseIO\Models\Ticket;
use AbuseIO\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        Contact::factory()->count(5)->create();
        Netblock::factory()->count(10)->create();
        Domain::factory()->count(10)->create();
        Account::factory()->count(4)->create();
        User::factory()->count(4)->create();
        Ticket::factory()->count(10)->create();

        // give the tickets some events
        Ticket::all()->each(function ($ticket) {
            $events = random_int(1, 24);
            Event::factory()->count($events)->create(['ticket_id' => $ticket->id]);
        });

        // give the tickets some notes
        Ticket::all()->each(function ($ticket) {
            $notes = random_int(1, 24);
            Note::factory()->count($notes)->create(['ticket_id' => $ticket->id]);
        });

        Role::factory()->create([
            'name'        => 'Abuse',
            'description' => 'Abusedesk User',
        ]);

        // Seed the permissions and roles AbuseIO uses.
        $this->call(RolePermissionSeeder::class);

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
