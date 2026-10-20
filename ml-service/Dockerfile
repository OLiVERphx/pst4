ARG PYTHON_IMAGE=python:3.13-slim
FROM ${PYTHON_IMAGE}
WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app ./app
COPY entrypoint.sh .
RUN chmod +x entrypoint.sh

# Usuario sin privilegios dentro del contenedor
RUN useradd --create-home ml
USER ml

ENV PORT=8001
EXPOSE 8001
CMD ["./entrypoint.sh"]
