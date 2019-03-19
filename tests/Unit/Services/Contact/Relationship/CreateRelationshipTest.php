<?php

namespace Tests\Unit\Services\Contact\Relationship;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Relationship\CreateRelationship;

class CreateRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_a_relationship()
    {
        $account = factory(Account::class)->create();
        $contactA = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id
        ]);

        $request = [
            'account_id' => $account->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $relationship = app(CreateRelationship::class)->execute($request);

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'account_id' => $account->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    public function test_it_throws_an_exception_if_relationship_type_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $contactA = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(CreateRelationship::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $contactA = factory(Contact::class)->create();
        $contactB = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(CreateRelationship::class)->execute($request);
    }

    public function test_it_creates_a_relationship_and_reverse()
    {
        $account = factory(Account::class)->create();
        $contactA = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipTypeA = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $request = [
            'account_id' => $account->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
            'relationship_type_id' => $relationshipTypeA->id,
        ];

        app(CreateRelationship::class)->execute($request);

        $this->assertDatabaseHas('relationships', [
            'account_id' => $account->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
            'relationship_type_id' => $relationshipTypeA->id,
        ]);
        $this->assertDatabaseHas('relationships', [
            'account_id' => $account->id,
            'contact_is' => $contactB->id,
            'of_contact' => $contactA->id,
            'relationship_type_id' => $relationshipTypeB->id,
        ]);
    }
}