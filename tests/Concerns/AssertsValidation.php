<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

/**
 * Posts bad input the way a browser form would and checks what the user ends up seeing:
 * a redirect back to the form, errors for exactly the expected fields, a readable message
 * for each (never a raw "validation.xxx" key) and the same message rendered on the page.
 */
trait AssertsValidation
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $fields  fields that must be reported
     * @return array<string, list<string>> the error messages by field
     */
    protected function assertInvalid(?Authenticatable $user, string $method, string $url, string $formUrl, array $payload, array $fields, ?string $bag = null): array
    {
        $client = $user ? $this->actingAs($user) : $this;

        $response = $client->from($formUrl)->{$method}($url, $payload);

        $response->assertRedirect($formUrl);
        $response->assertSessionHasErrors($fields, null, $bag ?? 'default');

        $errors = session('errors')->getBag($bag ?? 'default')->toArray();

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $this->assertNotEmpty($message);
                $this->assertDoesNotMatchRegularExpression('/^(validation|auth|passwords)\./', $message, "Raw translation key shown for {$field}");
                $this->assertStringNotContainsString(':attribute', $message);
                $this->assertDoesNotMatchRegularExpression('/(?<![\w])\:[a-z_]{3,}\b/', $message, "Placeholder left in the message for {$field}: {$message}");
            }
        }

        // Only the expected fields fail (a rule that blames some other field is a bug in the form or the rules).
        $this->assertEqualsCanonicalizing($fields, array_keys($errors), 'Unexpected set of failing fields');

        $page = $client->followingRedirects()->from($formUrl)->{$method}($url, $payload);
        $html = $page->getContent();

        foreach (Arr::flatten($errors) as $message) {
            $this->assertStringContainsString(e($message), $html, "The page does not show: {$message}");
        }

        return $errors;
    }
}
