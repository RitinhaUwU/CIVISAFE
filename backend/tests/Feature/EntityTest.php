<?php

it('blocks unauthenticated access', function () {

    $response = $this->getJson('/api/v1/entities');

    $response->assertStatus(401);
});
