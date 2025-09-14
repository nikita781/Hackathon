<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Итоговый протокол</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .team-name {
            font-weight: bold;
            text-align: left;
        }

        .members {
            text-align: left;
            font-size: 11px;
        }
    </style>
</head>
<body>
<h1>
    ИТОГОВЫЙ ПРОТОКОЛ<br>
    Хакатона под названием «{{ $hackathon->title }}»
</h1>

<table>
    <thead>
    <tr>
        <th style="width: 8%">Место</th>
        <th style="width: 25%">Команда</th>
        <th style="width: 45%">Состав</th>
        <th style="width: 12%">Результат (баллы)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($teams as $team)
        <tr>
            <td>{{ $team->place }}</td>
            <td class="team-name">{{ $team->title }}</td>
            <td class="members">
                @foreach($team->users as $user)
                    {{ $user->name ?? $user->nickname }}<br>
                @endforeach
            </td>
            <td>{{ $team->avg_score ?? '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
