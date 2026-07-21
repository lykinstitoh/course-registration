<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Letter - {{ $application->reference }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1e3a8a; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #1e3a8a; font-size: 24px; text-transform: uppercase; }
        .header h3 { margin: 5px 0 0 0; color: #666; font-weight: normal; }
        .date-ref { text-align: right; margin-bottom: 30px; }
        .salutation { font-weight: bold; margin-bottom: 20px; }
        .content p { margin-bottom: 15px; }
        .details-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .details-table th, .details-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .details-table th { background-color: #f8fafc; width: 30%; }
        .footer { margin-top: 50px; }
        .signature { margin-top: 40px; }
        .signature p { margin: 0; }
        .signature-title { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('ocrs.institution_name', 'OCRS University') }}</h1>
        <h3>OFFICE OF THE REGISTRAR (ACADEMIC AFFAIRS)</h3>
    </div>

    <div class="date-ref">
        <p><strong>Date:</strong> {{ now()->format('d F Y') }}</p>
        <p><strong>Ref:</strong> {{ $application->reference }}</p>
    </div>

    <div class="salutation">
        Dear {{ $application->studentProfile->user->name }},
    </div>

    <div class="content">
        <p><strong>RE: OFFER OF ADMISSION</strong></p>
        <p>Following your recent application, I am pleased to inform you that you have been offered admission to {{ config('ocrs.institution_name', 'OCRS University') }} to pursue the following programme:</p>

        <table class="details-table">
            <tr>
                <th>Programme:</th>
                <td>{{ $application->programme->name }}</td>
            </tr>
            <tr>
                <th>Intake:</th>
                <td>{{ $application->intake->name }}</td>
            </tr>
            <tr>
                <th>Admission Number:</th>
                <td>{{ $application->studentProfile->admission_number }}</td>
            </tr>
        </table>

        <p>Please note that this offer is subject to verification of your original academic and identity documents. You are required to log into the student portal, navigate to the Enrollment checklist, and upload the mandatory documents for verification.</p>
        
        <p>Upon verification of your documents and payment of the required tuition fee, you will be able to proceed with your course registration.</p>

        <p>We look forward to welcoming you to the university.</p>
    </div>

    <div class="signature">
        <p>Yours faithfully,</p>
        <br><br><br>
        <p class="signature-title">Registrar (Academic Affairs)</p>
        <p>{{ config('ocrs.institution_name', 'OCRS University') }}</p>
    </div>
</body>
</html>
