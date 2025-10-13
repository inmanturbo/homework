<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class SelectOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'required|string',
            'state' => 'required|string',
            'client_id' => 'required|string',
            'redirect_uri' => 'nullable|string',
        ];
    }

    public function selectOrganization(): Response
    {
        $organizationId = $this->input('organization_id');

        $this->session()->put('selected_organization_id', $organizationId);

        if ($userId = $this->user()?->id) {
            cache()->put("org_selection:{$userId}", $organizationId, now()->addMinutes(5));
        }

        return redirect('/oauth/authorize?' . http_build_query([
            'client_id' => $this->input('client_id'),
            'state' => $this->input('state'),
            'redirect_uri' => $this->input('redirect_uri'),
            'response_type' => 'code',
        ]));
    }
}
