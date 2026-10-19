import os
import sys

# La clave debe existir antes de importar la app (Config se lee al importar)
os.environ["ML_API_KEY"] = "clave-de-prueba"
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
