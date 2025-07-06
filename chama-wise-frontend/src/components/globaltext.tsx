import { Text } from 'react-native';

export default function GlobalText({ children, style }) {
  return <Text style={[{ color: '#000' }, style]}>{children}</Text>;
}
