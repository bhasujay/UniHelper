import sys

if len(sys.argv) != 3:
    print("Usage: python simple_calculator.py <number1> <number2>")
    sys.exit(1)
    
number1 = int(sys.argv[1])
number2 = int(sys.argv[2])
print(f"The sum of {number1} and {number2} is {number1 + number2}")

print("The arguments are: ", end=" ")
for arg in sys.argv:
    print(arg, end=" ")
print()

print("Hello, this is from the python file")
print("You go the idea to implemnt the Z-Score checker in the python file")