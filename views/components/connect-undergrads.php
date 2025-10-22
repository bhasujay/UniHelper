<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undergraduate Connections</title>
    <!-- Removed internal styles - now using app-cards.css -->
</head>
<body>
    <div class="card-container">
        <?php
            // 2D array holding the data for 10 undergraduates
            $undergraduates = [
                [
                    "name" => "Sarah Perera",
                    "degree" => "BSc Computer Science",
                    "university" => "University of Colombo (UCSC)"
                ],
                [
                    "name" => "Rahul Fernando",
                    "degree" => "BEng Software Engineering",
                    "university" => "University of Moratuwa"
                ],
                [
                    "name" => "Nimali Silva",
                    "degree" => "BSc Information Technology",
                    "university" => "SLIIT"
                ],
                [
                    "name" => "Dinesh Karunaratne",
                    "degree" => "BSc Physical Science",
                    "university" => "University of Peradeniya"
                ],
                [
                    "name" => "Fathima Rizvi",
                    "degree" => "BBA Marketing Management",
                    "university" => "University of Sri Jayewardenepura"
                ],
                [
                    "name" => "Sanjay Vithanage",
                    "degree" => "BEng Electronic & Telecom",
                    "university" => "University of Moratuwa"
                ],
                [
                    "name" => "Priya Selvanayagam",
                    "degree" => "BSc Business Information Systems",
                    "university" => "NSBM Green University"
                ],
                [
                    "name" => "Kasun Rajapaksa",
                    "degree" => "BSc (Hons) in Medicine (MBBS)",
                    "university" => "University of Kelaniya"
                ],
                [
                    "name" => "Ishani Gunasekara",
                    "degree" => "BA (Hons) in English",
                    "university" => "University of Kelaniya"
                ],
                [
                    "name" => "Mihiran Jayasundara",
                    "degree" => "BSc Engineering",
                    "university" => "University of Ruhuna"
                ]
            ];

            // Loop through the array and generate a card for each person
            foreach ($undergraduates as $person) {
                // Using htmlspecialchars to prevent XSS attacks
                $name = htmlspecialchars($person['name']);
                $degree = htmlspecialchars($person['degree']);
                $university = htmlspecialchars($person['university']);

                echo "
                <div class='connect-card-horizontal'>
                    <div class='undergrad-avatar'></div>
                    
                    <div class='undergrad-info'>
                        <h3 class='undergrad-name'>{$name}</h3>
                        <p class='undergrad-degree'>{$degree}</p>
                        <p class='undergrad-university'>{$university}</p>
                    </div>
            
                    <a href='#' class='connect-button'>Connect</a>
                </div>
                ";
            }
        ?>
    </div>
</body>
</html>