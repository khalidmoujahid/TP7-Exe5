<?php
// Définir le fichier de stockage
$fichier_messages = 'messages.txt';
$messages = [];
$erreur = '';
$succes = false;

// Lire les messages existants
if (file_exists($fichier_messages)) {
    $contenu = file_get_contents($fichier_messages);
    if ($contenu) {
        $messages = json_decode($contenu, true) ?: [];
    }
}

// Trier les messages par date (du plus récent au plus ancien)
usort($messages, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($nom) || empty($message)) {
        $erreur = 'Tous les champs sont obligatoires.';
    } elseif (strlen($nom) < 2) {
        $erreur = 'Le nom doit contenir au moins 2 caractères.';
    } elseif (strlen($nom) > 50) {
        $erreur = 'Le nom ne doit pas dépasser 50 caractères.';
    } elseif (strlen($message) < 5) {
        $erreur = 'Le message doit contenir au moins 5 caractères.';
    } elseif (strlen($message) > 1000) {
        $erreur = 'Le message ne doit pas dépasser 1000 caractères.';
    } else {
        // Préparer le nouveau message
        $nouveau_message = [
            'id' => uniqid(),
            'nom' => htmlspecialchars($nom),
            'message' => nl2br(htmlspecialchars($message)),
            'date' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR']
        ];
        
        // Ajouter au début du tableau
        array_unshift($messages, $nouveau_message);
        
        // Limiter à 100 messages maximum
        if (count($messages) > 100) {
            $messages = array_slice($messages, 0, 100);
        }
        
        // Sauvegarder dans le fichier
        if (file_put_contents($fichier_messages, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $succes = true;
            
            // Réinitialiser le formulaire
            $nom = '';
            $message = '';
        } else {
            $erreur = 'Erreur lors de l\'enregistrement du message.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livre d'or - Guestbook</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .form-container {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            font-family: inherit;
        }
        
        textarea {
            height: 120px;
            resize: vertical;
        }
        
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 14px 28px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #45a049;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: #ffe6e6;
            border: 1px solid #ffcccc;
            color: #cc0000;
        }
        
        .alert-success {
            background-color: #e6ffe6;
            border: 1px solid #ccffcc;
            color: #006600;
        }
        
        .messages-container {
            padding: 30px;
        }
        
        .message-count {
            font-size: 18px;
            margin-bottom: 20px;
            color: #666;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .message {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ddd;
        }
        
        .message-author {
            font-weight: bold;
            color: #667eea;
            font-size: 18px;
        }
        
        .message-date {
            color: #888;
            font-size: 14px;
        }
        
        .message-content {
            color: #444;
            line-height: 1.8;
        }
        
        .empty-messages {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #888;
            font-size: 14px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 600px) {
            .container {
                border-radius: 0;
            }
            
            header {
                padding: 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .form-container,
            .messages-container {
                padding: 20px;
            }
            
            .message-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .message-date {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📖 Livre d'or</h1>
            <p class="subtitle">Laissez un message pour partager votre expérience</p>
        </header>
        
        <div class="form-container">
            <?php if ($erreur): ?>
                <div class="alert alert-error">
                    <strong>Erreur :</strong> <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($succes): ?>
                <div class="alert alert-success">
                    <strong>Merci !</strong> Votre message a été ajouté avec succès.
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="nom">Votre nom :</label>
                    <input type="text" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($nom); ?>"
                           placeholder="Entrez votre nom" 
                           maxlength="50" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="message">Votre message :</label>
                    <textarea id="message" name="message" 
                              placeholder="Écrivez votre message ici..." 
                              maxlength="1000" 
                              required><?php echo htmlspecialchars($message); ?></textarea>
                </div>
                
                <button type="submit">Publier le message</button>
            </form>
        </div>
        
        <div class="messages-container">
            <div class="message-count">
                <?php 
                $count = count($messages);
                echo $count == 0 ? 'Aucun message pour le moment.' : 
                     ($count == 1 ? '1 message' : $count . ' messages');
                ?>
            </div>
            
            <?php if (empty($messages)): ?>
                <div class="empty-messages">
                    <p>Soyez le premier à laisser un message !</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message">
                        <div class="message-header">
                            <div class="message-author"><?php echo $msg['nom']; ?></div>
                            <div class="message-date">
                                <?php 
                                $date = new DateTime($msg['date']);
                                echo $date->format('d/m/Y à H:i');
                                ?>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo $msg['message']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <footer>
            <p>© <?php echo date('Y'); ?> - Livre d'or en PHP | Messages stockés dans un fichier texte</p>
        </footer>
    </div>
</body>
</html>