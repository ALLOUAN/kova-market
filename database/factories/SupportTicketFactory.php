<?php

namespace Database\Factories;

use App\Enums\ContactSubject;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => ContactSubject::Order,
            'title' => fake()->sentence(4),
            'status' => SupportTicketStatus::Open,
            'last_message_at' => now(),
        ];
    }

    /**
     * With the customer's first message.
     */
    public function withMessage(string $body = 'Bonjour, ma commande n’est pas arrivée.'): static
    {
        return $this->afterCreating(fn (SupportTicket $ticket) => $ticket->messages()->create([
            'user_id' => $ticket->user_id,
            'from_staff' => false,
            'body' => $body,
        ]));
    }
}
