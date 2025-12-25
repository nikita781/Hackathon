<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $hackathonTitle }} - Сертификат</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
        }

        .certificate {
            background: white;
            width: 1200px;
            height: 800px;
            border: 8px solid #101011;
            border-radius: 20px;
            padding: 60px;
            position: relative;
        }

        .certificate::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px solid #E80024;
            border-radius: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .badge {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            position: relative;
        }

        .hexagon {
            width: 100px;
            height: 100px;
            background: #ecf0f1;
            border: 3px solid #101011;
            position: relative;
            margin: 10px auto;
            transform: rotate(30deg);
        }

        .hexagon::before,
        .hexagon::after {
            content: '';
            position: absolute;
            width: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
        }

        .hexagon::before {
            bottom: 100%;
            border-bottom: 29px solid #101011;
        }

        .hexagon::after {
            top: 100%;
            border-top: 29px solid #101011;
        }

        .ribbon {
            background: #E80024;
            color: white;
            padding: 8px 20px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            border-radius: 4px;
            z-index: 10;
        }

        .cert-type {
            color: #7f8c8d;
            font-size: 14px;
            letter-spacing: 3px;
            margin-bottom: 20px;
            padding-top: 10px;
        }

        .main-title {
            font-size: 48px;
            font-weight: bold;
            color: #101011;
            margin-bottom: 40px;
            line-height: 1.2;
        }

        .content {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .issued-to {
            color: #7f8c8d;
            font-size: 14px;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        .winner-name {
            font-size: 32px;
            font-weight: bold;
            color: #101011;
            margin-bottom: 30px;
        }

        .logo {
            width: 230px;
        }

        .description {
            color: #7f8c8d;
            font-size: 24px;
            line-height: 1.6;
            max-width: 700px;
            margin: 0 auto 40px;
            text-align: center;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
            position: absolute;
            bottom: 60px;
            left: 60px;
            right: 60px;
        }

        .signature-section {
            text-align: left;
        }

        .signature {
            width: 150px;
            height: 60px;
            margin-bottom: 10px;
        }

        .signature-text {
            font-style: italic;
            color: #7f8c8d;
            font-size: 12px;
        }

        .center-info {
            text-align: center;
            flex-grow: 1;
        }

        .organization {
            font-size: 18px;
            font-weight: bold;
            color: #101011;
            margin-bottom: 10px;
        }

        .date-id {
            color: #7f8c8d;
            font-size: 14px;
        }

        .seal-section {
            text-align: right;
        }

        .seal {
            width: 80px;
            height: 80px;
            margin-bottom: 10px;
        }

        .place-badge {
            background: #E80024;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            position: absolute;
            top: 50px;
            right: 50px;
            box-shadow: 0 4px 15px #10101191;
        }

        .gradient_text {
            color: #E80024;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .certificate {
                box-shadow: none;
                border: 2px solid #101011;
            }
        }
    </style>
</head>

<body>
<div class="certificate">
    <div class="place-badge">{{ $place }} место</div>

    <div class="header">
        <div class="badge">
            <div class="hexagon"></div>
            <div class="ribbon">ХАКАТОН</div>
        </div>
        <div class="cert-type">СЕРТИФИКАТ ПОБЕДИТЕЛЯ</div>
        <h1 class="main-title gradient_text">{{ $hackathonTitle }}</h1>
    </div>

    <div class="content" style="text-align: center; margin-bottom: 40px;">
        <div class="issued-to" style="display:block; margin-bottom:10px;">ВРУЧАЕТСЯ ПОЛЬЗОВАТЕЛЮ</div>
        <div class="winner-name" style="margin-bottom:15px;">{{ $userName ?? $userNickname}}</div>
        <div class="description" style="margin:0 auto; max-width:700px; line-height:1.4;">
            @if(isset($description) && $description)
                {{ $description }}
            @else
                Обладатель данного сертификата продемонстрировал выдающиеся навыки и достиг высоких результатов в турнире, заняв призовое место среди участников.
            @endif
        </div>
    </div>

    <table style="width:100%; margin-top:40px; border-collapse:collapse;">
        <tr>
            <td style="width:33%; text-align:left; vertical-align:top;">
                <div class="signature-section">
                    <h1 class="logo" style="margin:0; color:#E80024;">{{ $organizatorNickname }}</h1>
                    <div class="signature-text" style="font-style:italic; font-size:12px;">*Организатор хакатона*</div>
                </div>
            </td>
            <td style="width:33%; text-align:center; vertical-align:top;">
                <div class="center-info">
                    <div class="organization" style="font-weight:bold; font-size:14px;">Хакатон проводился с {{ $startTime }} по {{ $endTime }}</div>
                    <div class="date-id" style="font-size:12px; color:#7f8c8d;">Выдан: {{ $endTime }}</div>
                </div>
            </td>
            <td style="width:33%; text-align:right; vertical-align:top;">
                <div class="seal-section" style="display:inline-block;">
                    @if(isset($seal) && $seal)
                        <img src="{{ $seal }}" alt="Печать" class="seal" style="width:120px; height:120px; display:block; margin-left:auto;">
                    @else
                        <div class="seal" style="width:80px; height:80px; border:2px dashed #bdc3c7; color:#bdc3c7; font-size:10px; text-align:center; line-height:80px; display:block; margin-left:auto;">
                            ПЕЧАТЬ
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>
</body>

</html>
