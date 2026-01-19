// sudoku-widget.js - Version 2.0.1
let generationCount = 0; // Correct single declaration
const generationLimit = 20; // Corrected limit to match previous instructions

function generateSudoku() {
    if (generationCount >= generationLimit) {
        alert('You have reached the maximum number of Sudoku generations.');
        return;
    }

    generationCount++; // Increment the counter
    let puzzleSize = 9; // Fixed size

    const difficultyElement = document.getElementById('difficulty-selector');
    if (!difficultyElement) {
        console.error("Difficulty selector element not found.");
        return;
    }
    let difficulty = difficultyElement.value;

    const puzzle = createPuzzle(puzzleSize, difficulty);
    displayPuzzle(puzzle);

    // Update the remaining generations
    const remaining = generationLimit - generationCount;
    document.getElementById('generation-remaining').textContent = `Remaining generations: ${remaining}`;

    // Disable the button if the limit is reached
    if (generationCount >= generationLimit) {
        document.querySelector('button[onclick="generateSudoku()"]').disabled = true;
    }
}
// Sudoku generation function
// This function generates a Sudoku puzzle of a given size and difficulty level.
function createPuzzle(size, difficulty) {
    let puzzle = new Array(size).fill().map(() => new Array(size).fill(0));
    fillGrid(puzzle);
    adjustNumbers(puzzle, difficulty);
    return puzzle;
}

function fillGrid(grid) {
    if (!solveGrid(grid, 0, 0)) {
        console.error("Failed to solve grid");
    }
}

function solveGrid(grid, row, col) {
    const size = grid.length;
    if (col === size) {
        col = 0;
        row++;
    }
    if (row === size) {
        return true;
    }

    if (grid[row][col] !== 0) {
        return solveGrid(grid, row, col + 1);
    }

    const numbers = shuffle(Array.from({ length: size }, (_, i) => i + 1));
    for (let num of numbers) {
        if (canPlaceNumber(grid, row, col, num)) {
            grid[row][col] = num;
            if (solveGrid(grid, row, col + 1)) {
                return true;
            }
            grid[row][col] = 0;
        }
    }
    return false;
}

function adjustNumbers(grid, difficulty) {
    const size = grid.length;
    const cellsToKeep = difficulty === 'easy' ? 61 : difficulty === 'medium' ? 40 : difficulty === 'hard' ? 26 : 17;
    let cellsFilled = size * size;
    while (cellsFilled > cellsToKeep) {
        let row = Math.floor(Math.random() * size);
        let col = Math.floor(Math.random() * size);
        if (grid[row][col] !== 0) {
            grid[row][col] = 0;
            cellsFilled--;
        }
    }
}

function canPlaceNumber(grid, row, col, num) {
    const size = grid.length;
    const boxSize = Math.sqrt(size);
    const startRow = Math.floor(row / boxSize) * boxSize;
    const startCol = Math.floor(col / boxSize) * boxSize;

    for (let i = 0; i < size; i++) {
        if (grid[row][i] === num || grid[i][col] === num) return false;
        let boxRow = startRow + Math.floor(i / boxSize);
        let boxCol = startCol + i % boxSize;
        if (grid[boxRow][boxCol] === num) return false;
    }
    return true;
}

function shuffle(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

function displayPuzzle(grid) {
    const tbody = document.getElementById('sudoku-grid').querySelector('tbody');
    tbody.innerHTML = '';
    for (let i = 0; i < grid.length; i++) {
        const tr = document.createElement('tr');
        for (let j = 0; j < grid[i].length; j++) {
            const td = document.createElement('td');
            const input = document.createElement('input');
            input.type = 'text';
            input.value = grid[i][j] ? grid[i][j] : '';
            input.disabled = grid[i][j] !== 0;
            td.appendChild(input);
            tr.appendChild(td);
        }
        tbody.appendChild(tr);
    }
}

window.addEventListener('load', () => {
    const defaultDifficulty = 'easy'; // Default difficulty for first load
    const puzzleSize = 9;
    const puzzle = createPuzzle(puzzleSize, defaultDifficulty);
    displayPuzzle(puzzle);

    // Update the remaining generations
    const remaining = generationLimit - generationCount;
    document.getElementById('generation-remaining').textContent = `Remaining generations: ${remaining}`;
});